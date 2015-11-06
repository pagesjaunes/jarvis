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

class LintScssCommand extends BaseBuildCommand
{
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;
    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    /**
     * @var string|null
     */
    private $workingDir;

    /**
     * @var string|null
     */
    private $scssLintRemoteBuildDir;

    /**
     * @var string|null
     */
    private $scssLintlocalBuildDir;

    /**
     * @var bool
     */
    private $copyGlobalConfigFile;

    /**
     * @var bool
     */
    private $openReportFile;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Static code analysis the sass files of sourcecode files');

        $this->addOption(
            'formatter',
            null,
            InputOption::VALUE_REQUIRED,
            'Output format Default|Files|JSON|Checkstyle (see [Formatters](https://github.com/brigade/scss-lint#formatters))',
            'JSON'
        );

        $this->addOption(
            'no-copy-global-config-file',
            null,
            InputOption::VALUE_NONE,
            'Disable the copy of the global config file to the home directory.'
        );

        $this->addOption(
            'open-report-file',
            null,
            InputOption::VALUE_NONE,
            'No open the report file.'
        );
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->copyGlobalConfigFile = !$input->getOption('no-copy-global-config-file');
        $this->openReportFile = $input->getOption('open-report-file');

        $this->formatter = $input->getOption('formatter');
        $this->requireLibrary = null;

        $this->scssLintRemoteBuildDir = sprintf('%s/scss-lint', $this->getRemoteBuildDir());
        $this->scssLintlocalBuildDir = sprintf('%s/scss-lint', $this->getLocalBuildDir());
        $this->getRemoteFilesystem()->mkdir($this->scssLintRemoteBuildDir);
        $this->getLocalFilesystem()->mkdir($this->scssLintlocalBuildDir);

        $this->installScssLint();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            $this->getDescription(),
            $projectName
        ));

        if ($this->copyGlobalConfigFile) {
            $this->copyLocalScssLintConfigFileToRemoteServer($projectConfig);
        }

        $commandOptions = '--format='.$this->formatter;

        $requireLibrary = $this->getRequireLibrary($this->formatter);

        if ($requireLibrary) {
            $commandOptions .= ' --require='.$requireLibrary;
        }

        $remoteReportFilePath = $this->getRemoteReportFilePath($this->formatter, $projectConfig);
        if ($remoteReportFilePath) {
            $commandOptions .= ' --out='.$remoteReportFilePath;
        }

        $this->getSshExec()->exec(
            strtr(
                'scss-lint %project_dir%/src %command_options%',
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                    '%command_options%' => $commandOptions
                ]
            )
        );

        if ($remoteReportFilePath && $this->scssLintRemoteBuildDir && $this->scssLintlocalBuildDir) {
            $localReportFilePath = str_replace(
                $this->scssLintRemoteBuildDir,
                $this->scssLintlocalBuildDir,
                $remoteReportFilePath
            );
            $this->getRemoteFilesystem()->copyRemoteFileToLocal(
                $remoteReportFilePath,
                $localReportFilePath
            );
            if ($this->openReportFile) {
                if ($this->getLocalFilesystem()->exists($localReportFilePath)) {
                    $this->openFile($localReportFilePath);
                }
            }
        }

        return $this->getSshExec()->getLastReturnStatus() > 1 ? self::EXIT_ERROR : self::EXIT_SUCCESS;
    }

    public function setWorkingDir($dir)
    {
        $this->workingDir = $dir;
    }

    protected function installScssLint()
    {
        $this->getSshExec()->exec('gem list scss_lint --installed --quiet 1>/dev/null || sudo gem install scss_lint');

        if ('Checkstyle' == $this->formatter) {
            $this->getSshExec()->exec('gem list scss_lint_reporter_checkstyle --installed --quiet 1>/dev/null || sudo gem install scss_lint_reporter_checkstyle');
        }
    }

    protected function getRequireLibrary($format)
    {
        if ('Checkstyle' == $format) {
            return 'scss_lint_reporter_checkstyle';
        }

        return;
    }

    protected function getRemoteReportFilePath($format, ProjectConfiguration $projectConfig)
    {
        switch ($format) {
            case 'Checkstyle':
                return strtr('%build_dir%/%project_name%.xml', [
                    '%project_name%' => $projectConfig->getProjectName(),
                    '%build_dir%' => $this->scssLintRemoteBuildDir
                ]);
            case 'JSON':
                return strtr('%build_dir%/%project_name%.json', [
                    '%project_name%' => $projectConfig->getProjectName(),
                    '%build_dir%' => $this->scssLintRemoteBuildDir
                ]);

            case 'Default':
            default:
                return;
        }
    }

    protected function copyLocalScssLintConfigFileToRemoteServer(ProjectConfiguration $projectConfig)
    {
        if (!$this->workingDir) {
            return;
        }

        $localScssLintConfigFile = strtr(
            '%working_dir%/.scss-lint.yml',
            [
                '%working_dir%' => $this->workingDir
            ]
        );
        if ($this->getLocalFilesystem()->exists($localScssLintConfigFile)) {
            $this->getRemoteFilesystem()->copyLocalFileToRemote(
                $localScssLintConfigFile,
                strtr(
                    '%home_dir%/.scss-lint.yml',
                    [
                        '%home_dir%' => $this->getRemoteFilesystem()->getHomeDirectory()
                    ]
                )
            );
        }
    }
}
