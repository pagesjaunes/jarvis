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

class TestUnitCommand extends BaseCommand
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
        $this->setDescription('Executes unit tests');

        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Formatter', 'pretty');
        $this->addOption('no-display-status-text', null, InputOption::VALUE_NONE, 'Do not display the status ');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->format = $input->getOption('format');
        $this->displayStatusText = !$input->getOption('no-display-status-text');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->getSshExec()->exec(
            strtr(
                'cd %project_dir% && phpspec run --format=%format% --stop-on-failure '.($output->isDebug() ? ' --verbose' : ''),
                [
                    '%format%' => $this->format,
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            )
        );

        $buildDir = $this->remoteBuildDir.'/tests';

        $this->getRemoteFilesystem()->mkdir($buildDir);

        $report = $this->getSshExec()->run(
            strtr(
                'cd %project_dir% && phpspec run --format=html --no-interaction > %build_dir%/unit.html',
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                    '%build_dir%' => $buildDir,
                ]
            )
        );

        $this->getRemoteFilesystem()->syncRemoteToLocal($this->remoteBuildDir, $this->localBuildDir);

        $statusCode = $this->getSshExec()->getLastReturnStatus() == 0 && strpos($report, 'broken') == false ? 0 : 1;

        if ($this->displayStatusText) {
            $output->writeln(
                sprintf(
                    '<comment>Executes unit tests for project "<info>%s</info>"</comment>: %s',
                    $projectName,
                    $statusCode == 0 ?
                        '<info>SUCCESS</info>'
                        :
                        '<error>ERROR</error>'
                )
            );
        }

        return $statusCode;
    }
}
