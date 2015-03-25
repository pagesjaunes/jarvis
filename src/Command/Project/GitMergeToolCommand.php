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
use Jarvis\Process\ExecAwareTrait;
use Jarvis\Project\ProjectConfiguration;

class GitMergeToolCommand extends BaseCommand
{
    use ExecAwareTrait;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Run merge conflict resolution tools to resolve merge conflicts');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->getExec()->run('git mergetool', null, $projectConfig->getLocalGitRepositoryDir());
    }
}
