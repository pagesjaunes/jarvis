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
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Gives metrics about PHP project and classes for to one or all projects');

        $this->addOption('self-update', null, InputOption::VALUE_NONE, 'Self update phpmetrics');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('self-update')) {
            $this->getSshExec()->exec('composer global require halleck45/phpmetrics');
        }
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $remoteBuildDir = sprintf('%s/metrics', $this->getRemoteBuildDir());
        $localBuildDir = sprintf('%s/metrics', $this->getLocalBuildDir());

        $reportFile = strtr('%build_dir%/phpmetrics/%project_name%.html', [
            '%project_name%' => $projectConfig->getProjectName(),
            '%build_dir%' => $remoteBuildDir
        ]);

        $this->getSshExec()->run(
            strtr(
                'mkdir -p %build_dir% && php-metrics --level=0 --report-html=%report_file% %project_dir%/src'.($output->isDebug() ? ' --verbose' : ''),
                [
                    '%report_file%' => $reportFile,
                    '%build_dir%' => $remoteBuildDir,
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            ),
            $output,
            OutputInterface::VERBOSITY_NORMAL
        );

        $this->getRemoteFilesystem()->syncRemoteToLocal($remoteBuildDir, $localBuildDir, ['delete' => true]);

        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $reportFile = strtr('%build_dir%/phpmetrics/%project_name%.html', [
                '%project_name%' => $projectConfig->getProjectName(),
                '%build_dir%' => $localBuildDir
            ]);
            if (file_exists($reportFile)) {
                $this->openFile($reportFile);
            }
        }

        return $this->getSshExec()->getLastReturnStatus();
    }
}
