<?php

/*
 * This file is part of the Jarvis package
 *
 * Copyright (c) 2015 Tony Dubreil
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Tony Dubreil <tonydubreil@gmail.com>
 */

namespace Jarvis\Command\Project;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;

class GitHookPreCommitCommand extends BaseCommand
{
    use \Jarvis\Process\PhpCsFixerAwareTrait;
    use \Jarvis\Process\ExecAwareTrait;
    use \Jarvis\Ssh\SshExecAwareTrait;
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;
    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;
    use \Jarvis\Symfony\RemoteConsoleExecAwareTrait;

    private $localTmpStagingAreaRootDir = '';
    private $remoteTmpStagingAreaRootDir;
    private $skeletonPhpCsFixerDir;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Performs checks before commiting');

        $this->addOption('php-cs-fixer-level', null, InputOption::VALUE_REQUIRED, 'The level of fixes (can be psr0, psr1, psr2, or symfony (formerly all))', 'symfony');

        $this->addOption('pattern', null, InputOption::VALUE_REQUIRED, 'The pattern file', '/\.(php|yml|twig)$/i');

        parent::configure();
    }

    public function setSkeletonPhpCsFixerDir($skeletonPhpCsFixerDir)
    {
        $this->skeletonPhpCsFixerDir = $skeletonPhpCsFixerDir;

        return $this;
    }

    public function setLocalTmpStagingAreaRootDir($dir)
    {
        $this->localTmpStagingAreaRootDir = $dir;
    }

    public function setRemoteTmpStagingAreaRootDir($dir)
    {
        $this->remoteTmpStagingAreaRootDir = $dir;
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->phpCsFixerLevel = $input->getOption('php-cs-fixer-level');
        $this->pattern = $input->getOption('pattern');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $exitCodeStatus = static::EXIT_SUCCESS;

        $localTemporaryCopyStagingAreaDir = $this->localTmpStagingAreaRootDir.'/'.$projectName;
        $remoteTmpStagingAreaRootDir = $this->remoteTmpStagingAreaRootDir.'/'.$projectName;

        $this->composerInstall($projectName, $output);

        $this->createTemporaryStagingAreaDirectory(
            $localTemporaryCopyStagingAreaDir,
            $remoteTmpStagingAreaRootDir,
            $output
        );

        $files = $this->extractCommitedFiles($projectConfig, $localTemporaryCopyStagingAreaDir, $output);

        if (0 == count($files)) {
            $output->writeln('No files to check');

            $this->removeTemporaryStagingAreaDirectory(
                $localTemporaryCopyStagingAreaDir,
                null,
                $output
            );

            if (static::EXIT_SUCCESS == $exitCodeStatus) {
                $exitCodeStatus = $this->unitTests($projectConfig, $output);
            }

            if (static::EXIT_SUCCESS == $exitCodeStatus) {
                $exitCodeStatus = $this->integrationTests($projectConfig, $output);
            }

            if (static::EXIT_SUCCESS == $exitCodeStatus) {
                $output->writeln('<info>Good job dude!</info>');
            } else {
                $output->writeln('<error>Please fix errors and type "git add"</error>');
            }

            return $exitCodeStatus;
        }

        $extensionsFound = [];
        foreach ($files as $file) {
            $extensionsFound[pathinfo($file, PATHINFO_EXTENSION)] = 1;
        }

        $this->synchronizeLocalStagingAreaToRemote(
            $localTemporaryCopyStagingAreaDir,
            $remoteTmpStagingAreaRootDir,
            $output
        );

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->checkComposerFiles($files, $projectName, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus && isset($extensionsFound['php'])) {
            $exitCodeStatus = $this->validatePhpSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectName, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus && isset($extensionsFound['yml'])) {
            $exitCodeStatus = $this->validateYamlSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectConfig, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus && isset($extensionsFound['twig'])) {
            $exitCodeStatus = $this->validateTwigSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectConfig, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus && isset($extensionsFound['scss'])) {
            $exitCodeStatus = $this->validateScssSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectConfig, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus && isset($extensionsFound['php'])) {
            $exitCodeStatus = $this->checkPhpCodeStyle($files, $remoteTmpStagingAreaRootDir, $projectName, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus && isset($extensionsFound['php'])) {
            $exitCodeStatus = $this->unitTests($projectConfig, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus && isset($extensionsFound['php'])) {
            $exitCodeStatus = $this->integrationTests($projectConfig, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $output->writeln('<info>Good job dude!</info>');
        } else {
            $output->writeln('<error>Please fix errors and type "git add"</error>');
        }

        $this->removeTemporaryStagingAreaDirectory(
            $localTemporaryCopyStagingAreaDir,
            $remoteTmpStagingAreaRootDir,
            $output
        );

        return $exitCodeStatus;
    }

    protected function composerInstall($projectName, OutputInterface $output)
    {
        $this->getApplication()->executeCommand('project:composer:install', [
            '--project-name' => $projectName
        ], $output);
    }

    protected function createTemporaryStagingAreaDirectory($localDir, $remoteDir, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $output->writeln(sprintf(
                '<comment>Create local temporary copy of staging area "<info>%s</info>"</comment>',
                $localDir
            ));
        }
        $this->getLocalFilesystem()->remove($localDir);
        $this->getLocalFilesystem()->mkdir($localDir);

        if ($this->skeletonPhpCsFixerDir && $this->getLocalFilesystem()->exists($this->skeletonPhpCsFixerDir.'/php_cs')) {
            $this->getLocalFilesystem()->copy($this->skeletonPhpCsFixerDir.'/php_cs', $localDir.'/.php_cs');
        }

        if ($output->isDebug()) {
            $output->writeln(sprintf(
                '<comment>Create remote temporary copy of staging area "<info>%s</info>"</comment>',
                $remoteDir
            ));
        }

        $this->getRemoteFilesystem()->remove($remoteDir);
        $this->getRemoteFilesystem()->mkdir($remoteDir);
    }

    protected function synchronizeLocalStagingAreaToRemote($localDir, $remoteDir, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $output->writeln(sprintf(
                '<comment>Remove local temporary copy of staging area from "<info>%s</info>" to "<info>%s</info>"</comment>',
                $localDir,
                $remoteDir
            ));
        }

        $this->getRemoteFilesystem()->mkdir($remoteDir);

        $this->getRemoteFilesystem()->syncLocalToRemote(
            $localDir,
            $remoteDir
        );
    }

    protected function removeTemporaryStagingAreaDirectory($localDir, $remoteDir, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $output->writeln(sprintf(
                '<comment>Remove local temporary copy of staging area "<info>%s</info>"</comment>',
                $localDir
            ));
        }

        $this->getLocalFilesystem()->remove($localDir);

        if ($remoteDir) {
            if ($output->isDebug()) {
                $output->writeln(sprintf(
                    '<comment>Remove remote temporary copy of staging area "<info>%s</info>"</comment>',
                    $remoteDir
                ));
            }
            $this->getRemoteFilesystem()->remove($remoteDir);
        }
    }

    protected function extractCommitedFiles(ProjectConfiguration $projectConfig, $tmpStaging, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Extract commited files',
            $projectConfig->getProjectName()
        ));

        $this->getExec()->exec(
            'git rev-parse --verify HEAD 2> /dev/null',
            $projectConfig->getLocalGitRepositoryDir()
        );

        // Initial commit: diff against an empty tree object
        $against = '4b825dc642cb6eb9a060e54bf8d69288fbee4904';
        if ($this->getExec()->getLastReturnStatus() == 0) {
            $against = 'HEAD';
        }

        $files = [];

        // retrieve all files in staging area that are added, modified or renamed but no deletions etc
        $result = $this->getExec()->exec(
            sprintf('git diff-index --cached --full-index --diff-filter=ACMR %s -- ', $against),
            $projectConfig->getLocalGitRepositoryDir()
        );

        // copy each committed file in a temporary location
        if ($this->getExec()->getLastReturnStatus() == 0 && !empty($result)) {

            foreach (explode(PHP_EOL, $result) as $data) {
                $parts = explode(' ', preg_replace('/\s+/i', ' ', $data));

                $sha = $parts[3];
                $filepath = $parts[5];

                if (strpos($filepath, 'vendor/') !== false) {
                    continue;
                }

                if (!preg_match($this->pattern, $filepath)) {
                    continue;
                }

                // Copy contents of staged version of files to temporary staging area
                // because we only want the staged version that will be commited and not
                // the version in the working directory
                $targetFile = sprintf('%s/%s', $tmpStaging, $filepath);

                $this->getLocalFilesystem()->mkdir(dirname($targetFile));

                $this->getExec()->run(
                    strtr(
                        'git cat-file blob %sha% > "%file%"',
                        [
                            '%sha%' => $sha,
                            '%dir%' => dirname($targetFile),
                            '%file%' => $targetFile
                        ]
                    ),
                    $output,
                    $projectConfig->getLocalGitRepositoryDir()
                );

                $files[] = $filepath;
            }
        }

        return $files;
    }

    protected function checkComposerFiles(array $files, $projectName, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Check commited files',
            $projectName
        ));

        $composerJsonFound = false;
        $composerLockFound = false;

        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_BASENAME);
            if ($filename == 'composer.json') {
                $composerJsonFound = true;
                $this->getApplication()->executeCommand('project:composer:validate', [
                    '--project-name' => $projectName
                ], $output);
            }

            if ($filename == 'composer.lock') {
                $composerLockFound = true;
            }
        }

        if ($composerJsonFound && !$composerLockFound) {
            $output->writeln('<error>The file composer.lock must be commited if the file composer.json is modified!</error>');
            return 1;
        }

        if (!$composerJsonFound && $composerLockFound) {
            $output->writeln('<error>The file composer.json must be commited if the file composer.lock is modified!</error>');
            return 1;
        }

        return 0;
    }


    protected function validatePhpSyntaxCheck(array $files, $remoteTmpStaging, $projectName, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Validate PHP code on syntax errors',
            $projectName
        ));

        $report = $this->getSshExec()->exec(
            strtr(
                'php-parallel-lint -e php -j 10 %dir%', // TODO: variable command name
                [
                    '%dir%' => $remoteTmpStaging
                ]
            )
        );

        // clean path without path temporary staging area for files with syntax errors
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                $report = str_replace($remoteTmpStaging, '', $report);
            }
        }

        if (static::EXIT_SUCCESS !== $this->getSshExec()->getLastReturnStatus()) {
            $output->writeln($report, OutputInterface::OUTPUT_RAW);
        }

        return false === strpos($report, 'Parse error') ? 0 : 1;
    }

    protected function validateYamlSyntaxCheck(array $files, $remoteTmpStaging, $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Validate YAML code on syntax errors',
            $projectConfig->getProjectName()
        ));

        $report = $this->getSymfonyRemoteConsoleExec()->exec(
            $projectConfig->getRemoteSymfonyConsolePath(),
            strtr(
                'lint:yaml %dir%',
                [
                '%dir%' => $remoteTmpStaging
                ]
            ),
            'dev'
        );

        // clean path without path temporary staging area for files with syntax errors
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'yml') {
                $report = str_replace($remoteTmpStaging, '', $report);
            }
        }

        if (static::EXIT_SUCCESS !== $this->getSshExec()->getLastReturnStatus()) {
            $output->writeln($report, OutputInterface::OUTPUT_RAW);
        }

        return $this->getSymfonyRemoteConsoleExec()->getLastReturnStatus();
    }

    protected function validateTwigSyntaxCheck(array $files, $remoteTmpStaging, $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Validate TWIG code on syntax errors',
            $projectConfig->getProjectName()
        ));

        $report = $this->getSymfonyRemoteConsoleExec()->exec(
            $projectConfig->getRemoteSymfonyConsolePath(),
            strtr(
                'lint:twig %dir%', // TODO: manage symfony version >= 2.7 lint:twig
                [
                '%dir%' => $remoteTmpStaging
                ]
            ),
            'dev'
        );

        // clean path without path temporary staging area for files with syntax errors
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'twig') {
                $report = str_replace($remoteTmpStaging, '', $report);
            }
        }

        if (static::EXIT_SUCCESS !== $this->getSshExec()->getLastReturnStatus()) {
            $output->writeln($report, OutputInterface::OUTPUT_RAW);
        }

        return $this->getSymfonyRemoteConsoleExec()->getLastReturnStatus();
    }

    protected function validateScssSyntaxCheck(array $files, $remoteTmpStaging, $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Validate SCSS code on syntax errors',
            $projectConfig->getProjectName()
        ));

        $this->getSshExec()->exec('which scss-lint || gem install scss-lint');

        $report = $this->getSshExec()->exec(
            strtr(
                'scss-lint %project_dir%/src',
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            )
        );

        // clean path without path temporary staging area for files with syntax errors
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'scss') {
                $report = str_replace($remoteTmpStaging, '', $report);
            }
        }

        if (static::EXIT_SUCCESS !== $this->getSshExec()->getLastReturnStatus()) {
            $output->writeln($report, OutputInterface::OUTPUT_RAW);
        }

        return $this->getSymfonyRemoteConsoleExec()->getLastReturnStatus();
    }

    protected function checkPhpCodeStyle(array $files, $remoteTmpStaging, $projectName, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Checking PHP code style',
            $projectName
        ));

        return $this->getPhpCsFixer()->fixRemoteDir(
            $remoteTmpStaging,
            $output,
            [
                'dry-run' => true
            ]
        );
    }

    protected function unitTests(ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Running unit tests',
            $projectConfig->getProjectName()
        ));

        return $this->getApplication()->executeCommand('project:tests:unit', [
            '--project-name' => $projectConfig->getProjectName(),
            '--no-display-status-text' => true
        ], $output);
    }

    protected function integrationTests(ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            'Running integration tests',
            $projectConfig->getProjectName()
        ));

        return $this->getApplication()->executeCommand('project:tests:integration', [
            '--project-name' => $projectConfig->getProjectName(),
            '--no-display-status-text' => true
        ], $output);
    }
}
