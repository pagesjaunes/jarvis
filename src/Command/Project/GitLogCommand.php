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

class GitLogCommand extends BaseGitCommand
{
    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {

        $this->gitCommand = 'log --graph --pretty=tformat:\'%Cred%h%Creset -%C(cyan)%d %Creset%s %Cgreen(%an %cr)%Creset\' --abbrev-commit --date=relative';

        parent::executeCommandByProject($projectName, $projectConfig, $output);
    }
}
