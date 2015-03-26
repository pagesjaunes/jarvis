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

    const EXIT_SUCCESS = 0;

    use \Jarvis\Process\ExecAwareTrait;
    use \Jarvis\Ssh\SshExecAwareTrait;
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;
    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;
    use \Jarvis\Symfony\RemoteConsoleExecAwareTrait;

    private $localTmpStagingAreaRootDir = '';
    private $remoteTmpStagingAreaRootDir;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Performs checks before commiting');

        $this->addOption('php-cs-fixer-level', null, InputOption::VALUE_REQUIRED, 'The level of fixes (can be psr0, psr1, psr2, or symfony (formerly all))', 'symfony');

        $this->addOption('pattern', null, InputOption::VALUE_REQUIRED, 'The pattern file', '/\.(php|yml|twig)$/i');
        // TODO: |js|scss|yml

        parent::configure();
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
        // $this->temporaryCopyStagingAreaName = $input->getOption('tmp-copy-staging-name');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $exitCodeStatus = static::EXIT_SUCCESS;
        $errorMessage = null;

        $localTemporaryCopyStagingAreaDir = $this->localTmpStagingAreaRootDir.'/'.$projectName;
        $remoteTmpStagingAreaRootDir = $this->remoteTmpStagingAreaRootDir.'/'.$projectName;

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

            return static::EXIT_SUCCESS;
        }

        $this->synchronizeLocalStagingAreaToRemote(
            $localTemporaryCopyStagingAreaDir,
            $remoteTmpStagingAreaRootDir,
            $output
        );

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->checkComposerFiles($files, $projectName, $output);
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->validatePhpSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectName, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'There are some PHP syntax errors!';
            // }
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->validateYamlSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectConfig, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'There are some YAML syntax errors!';
            // }
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->validateTwigSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectConfig, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'There are some TWIG syntax errors!';
            // }
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->validateScssSyntaxCheck($files, $remoteTmpStagingAreaRootDir, $projectConfig, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'There are some SCSS syntax errors!';
            // }
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->checkPhpCodeStyle($files, $remoteTmpStagingAreaRootDir, $projectName, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'There are coding standards violations!';
            // }
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->checkPhpMd($files, $remoteTmpStagingAreaRootDir, $projectName, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'There are PHPMD violations!';
            // }
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->unitTests($projectConfig, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'Fix the unit tests!';
            // }
        }

        if (static::EXIT_SUCCESS == $exitCodeStatus) {
            $exitCodeStatus = $this->integrationTests($projectConfig, $output);
            // if (static::EXIT_SUCCESS !== $exitCodeStatus) {
            //     $errorMessage = 'Fix the integration tests!';
            // }
        }

        if ($errorMessage) {
            $output->writeln(sprintf('<error>%s</error>', $errorMessage));
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

        if ($output->isDebug()) {
            $output->writeln(sprintf(
                '<comment>Create remote temporary copy of staging area "<info>%s</info>"</comment>',
                $remoteDir
            ));
        }

        $this->getRemoteFilesystem()->remove($remoteDir);
        $this->getRemoteFilesystem()->mkdir($remoteDir);
    }

    protected function synchronizeLocalStagingAreaToRemote($localDir, $remoteDir = null, OutputInterface $output)
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

    protected function removeTemporaryStagingAreaDirectory($localDir, $remoteDir = null, OutputInterface $output)
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
        if ($result) {
            foreach ($result as $data) {
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

        ob_start();
        $this->getSshExec()->exec(
            strtr(
                'parallel-lint -e php -j 10 %dir%',
                [
                    '%dir%' => $remoteTmpStaging
                ]
            )
        );
        $report = ob_get_clean();

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
        $found = false;
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'yml') {
                $found = true;
                break;
            }
        }

        if ($found) {
            $output->writeln(sprintf(
                '<comment>%s for project "<info>%s</info>"</comment>',
                'Validate YAML code on syntax errors',
                $projectConfig->getProjectName()
            ));

            ob_start();
            $this->getSymfonyRemoteConsoleExec()->exec(
                $projectConfig->getRemoteSymfonyConsolePath(),
                strtr(
                    'yaml:lint %dir%',
                    [
                    '%dir%' => $remoteTmpStaging
                ]),
                'dev'
            );
            $report = ob_get_clean();

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

        return 0;
    }

    protected function validateTwigSyntaxCheck(array $files, $remoteTmpStaging, $projectConfig, OutputInterface $output)
    {
        $found = false;
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'twig') {
                $found = true;
                break;
            }
        }

        if ($found) {
            $output->writeln(sprintf(
                '<comment>%s for project "<info>%s</info>"</comment>',
                'Validate TWIG code on syntax errors',
                $projectConfig->getProjectName()
            ));

            ob_start();
            $this->getSymfonyRemoteConsoleExec()->exec(
                $projectConfig->getRemoteSymfonyConsolePath(),
                strtr(
                    'twig:lint %dir%',
                    [
                    '%dir%' => $remoteTmpStaging
                ]),
                'dev'
            );
            $report = ob_get_clean();

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

        return 0;
    }

    protected function validateScssSyntaxCheck(array $files, $remoteTmpStaging, $projectConfig, OutputInterface $output)
    {
        $found = false;
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'scss') {
                $found = true;
                break;
            }
        }

        if ($found) {
            $output->writeln(sprintf(
                '<comment>%s for project "<info>%s</info>"</comment>',
                'Validate SCSS code on syntax errors',
                $projectConfig->getProjectName()
            ));

            ob_start();

            $this->getSshExec()->exec('which scss-lint || gem install scss-lint');

            $this->getSshExec()->exec(
                strtr(
                    'scss-lint %project_dir%/src',
                    [
                        '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                    ]
                )
            );
            $report = ob_get_clean();

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

        return 0;
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
                'level' => $this->phpCsFixerLevel,
                'dry-run' => true
            ]
        );
    }

    protected function checkPhpMd(array $files, $remoteTmpStaging, $projectName, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>Checking code mess with PHPMD for project "<info>%s</info>"</comment>',
            $projectName
        ));

        $this->getSshExec()->exec(
            strtr(
                'phpmd %dir% text controversial,naming',
                [
                    '%dir%' => $remoteTmpStaging,
                ]
            )
        );

        return $this->getSshExec()->getLastReturnStatus();
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
