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

class GitStatusCommand extends BaseGitCommand
{
    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Git status for to one or all projects');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Git status for project "<info>%s</info>"</comment>',
                $projectName
            )
        );

        if (!is_dir($projectConfig->getLocalGitRepositoryDir())) {
            throw new \RuntimeException(sprintf('The directory "%s" does not exist', $projectConfig->getLocalGitRepositoryDir()));
        }

        ob_start();
        $this->getExec()->passthru('git status', $projectConfig->getLocalGitRepositoryDir());
        $output->writeln(
            strtr(
                ob_get_clean(),
                [
                'up-to-date' => '<info>up-to-date</info>',
                'is behind' => '<info>is behind</info>',
                'can be fast-forwarded' => '<info>can be fast-forwarded</info>',
                'modified:' => '<info>modified:</info>',
                'both modified' => '<info>both modified:</info>',
                'deleted:' => '<error>deleted:</error>', // red color
                'deleted by us' => '<error>deleted by us</error>', // red color
                ]
            )
        );
    }
}
