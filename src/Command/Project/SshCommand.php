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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;

class SshCommand extends BaseCommand
{
    use \Jarvis\Ssh\SshExecAwareTrait;

    /**
     * @var string|null
     */
    private $commandName;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setDescription('This command will SSH into a running remote machine and give you access to a shell in project directory.');

        $this->addArgument(
            'command_name',
            InputArgument::OPTIONAL,
            'Which command to run using ssh with working directory to project',
            'bash'
        );
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getArgument('command_name')) {
            $this->commandName = $input->getArgument('command_name');
        }
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->getSshExec()->passthru(
            strtr(
                'cd %project_dir%;%command_name%',
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                    '%command_name%' => escapeshellcmd($this->commandName)
                ]
            )
        );

        return $this->getSshExec()->getLastReturnStatus();
    }
}
