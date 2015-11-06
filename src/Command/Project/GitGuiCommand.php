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

class GitGuiCommand extends BaseGitCommand
{
    /**
     * @var string
     */
    private $executable;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Opens Git gui for to one or all projects');

        parent::configure();

        $this->addOption('client-name', null, InputOption::VALUE_REQUIRED, 'Which GUI Client to use', $this->getDefaultClient());
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->executable = $input->getOption('client-name');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Opens Git gui for the project "<info>%s</info>"</comment>',
                $projectName
            )
        );

        if (!is_dir($projectConfig->getLocalGitRepositoryDir())) {
            throw new \RuntimeException(sprintf(
                'The directory "%s" does not exist',
                $projectConfig->getLocalGitRepositoryDir()
            ));
        }

        $this->getExec()->passthru(
            $this->executable,
            $projectConfig->getLocalGitRepositoryDir()
        );
    }

    /**
     * @return string
     */
    protected function getDefaultClient()
    {
        if (strtoupper(PHP_OS) === 'DARWIN') {
            return 'gitup';
        } else {
            return 'gitk';
        }
    }
}
