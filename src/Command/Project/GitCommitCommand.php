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

use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;

class GitCommitCommand extends BaseGitCommand
{
    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Git commit to one or all projects');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Git commit for the project "<info>%s</info>"</comment>',
                $projectName
            )
        );

        if (!is_dir($projectConfig->getLocalGitRepositoryDir())) {
            throw new \RuntimeException(sprintf('The directory "%s" does not exist', $projectConfig->getLocalGitRepositoryDir()));
        }

        $this->getExec()->passthru('git commit -v', $projectConfig->getLocalGitRepositoryDir());
    }
}
