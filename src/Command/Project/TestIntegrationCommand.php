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
use Jarvis\Filesystem\RemoteFilesystemAwareTrait;
use Jarvis\Project\ProjectConfiguration;
use Jarvis\Ssh\SshExecAwareTrait;

class TestIntegrationCommand extends BaseCommand
{
    use SshExecAwareTrait;

    use RemoteFilesystemAwareTrait;

    /**
     * @var string
     */
    private $remoteBuildDir;

    /**
     * @var string
     */
    private $localBuildDir;

    /**
     * @var bool
     */
    private $displayStatusText;

    /**
     * Sets the value of remoteBuildDir.
     *
     * @param string $remoteBuildDir the remote build dir
     *
     * @return self
     */
    public function setRemoteBuildDir($remoteBuildDir)
    {
        $this->remoteBuildDir = $remoteBuildDir;

        return $this;
    }

    /**
     * Sets the value of localBuildDir.
     *
     * @param string $localBuildDir the local build dir
     *
     * @return self
     */
    public function setLocalBuildDir($localBuildDir)
    {
        $this->localBuildDir = $localBuildDir;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Executes integration tests');
        $this->addOption('no-display-status-text', null, InputOption::VALUE_NONE, 'Do not display the status ');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->displayStatusText = !$input->getOption('no-display-status-text');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->getApplication()->executeCommand('project:symfony:cache:clear', [
            '--project-name' => $projectName,
            '--symfony-env' => ['test']
        ], $output);

        $buildReportHtmlPath = !empty($this->remoteBuildDir) ? '--testdox-html '.$this->remoteBuildDir.'/tests/integration.html' : null;

        $this->getSshExec()->passthru(
            strtr(
                'phpunit --configuration %remote_phpunit_configuration_xml_path%  --colors %build_report_html% '.($output->isDebug() ? ' --verbose --debug' : ''),
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                    '%remote_phpunit_configuration_xml_path%' => $projectConfig->getRemotePhpunitConfigurationXmlPath(),
                    '%build_report_html%' => $buildReportHtmlPath,
                ]
            )
        );

        if ($this->displayStatusText) {
            $output->writeln(
                sprintf(
                    '<comment>Executes integration tests for project "<info>%s</info>"</comment>: %s',
                    $projectName,
                    $this->getSshExec()->getLastReturnStatus() == 0 ?
                    ' <info>SUCCESS</info>'
                    :
                    ' <error>ERROR</error>'
                )
            );
        }

        return $this->getSshExec()->getLastReturnStatus();
    }
}
