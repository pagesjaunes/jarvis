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

class PhpMetricsCommand extends BaseBuildCommand
{
    /**
     * @var string
     */
    private $remoteBuildDir;

    /**
     * @var string
     */
    private $localBuildDir;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Gives metrics about PHP project and classes for to one or all projects');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->remoteBuildDir = sprintf('%s/metrics', $this->getRemoteBuildDir());
        $this->localBuildDir = sprintf('%s/metrics', $this->getLocalBuildDir());

        $this->getRemoteFilesystem()->mkdir($this->remoteBuildDir);
        $this->getLocalFilesystem()->mkdir($this->localBuildDir);
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $remoteReportFilePath = strtr('%build_dir%/%project_name%.html', [
            '%project_name%' => $projectConfig->getProjectName(),
            '%build_dir%' => $this->remoteBuildDir
        ]);

        // Analyse source project code
        $this->getSshExec()->passthru(
            strtr(
                'mkdir -p %build_dir% && /usr/local/bin/phpmetrics --level=0 --report-html=%report_file% %project_dir%/src'.($output->isDebug() ? ' --verbose' : ''),
                [
                    '%report_file%' => $remoteReportFilePath,
                    '%build_dir%' => $this->remoteBuildDir,
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            )
        );

        $localReportFilePath = str_replace(
            $this->remoteBuildDir,
            $this->localBuildDir,
            $remoteReportFilePath
        );
        $this->getRemoteFilesystem()->copyRemoteFileToLocal(
            $remoteReportFilePath,
            $localReportFilePath
        );
        if ($this->getLocalFilesystem()->exists($localReportFilePath)) {
            $this->openFile($localReportFilePath);
        }

        return $this->getSshExec()->getLastReturnStatus();
    }
}
