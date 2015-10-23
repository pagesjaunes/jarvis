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

class LintPhpCommand extends BaseBuildCommand
{
    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Static code analysis the php files of sourcecode files');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            $this->getDescription(),
            $projectName
        ));

        if (strpos($projectName, 'bundle') === false) {
            $commandLine = 'php-parallel-lint -e php -j 10 %project_dir%/app %project_dir%/src %project_dir%/web --exclude %project_dir%/vendor';
        } else {
            $commandLine = 'php-parallel-lint -e php -j 10 %project_dir%/src';
        }

        $this->getSshExec()->run(
            strtr(
                $commandLine,
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            ),
            $output,
            OutputInterface::VERBOSITY_NORMAL
        );

        return $this->getSshExec()->getLastReturnStatus();
    }
}
