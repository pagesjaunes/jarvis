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

class LintTwigCommand extends BaseSymfonyCommand
{
    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Lints a template TWIG and outputs encountered errors');

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

        if (! file_exists($projectConfig->getLocalVendorDir())) {
            $this->getApplication()->executeCommand('project:composer:install', [
                '--project-name' => $projectName,
            ], $output);
        }

        $this->getSymfonyRemoteConsoleExec()->run(
            $projectConfig->getRemoteSymfonyConsolePath(),
            strtr(
                'twig:lint %project_dir%/src',
                [
                '%project_dir%' => $projectConfig->getRemoteWebappDir(),
            ]),
            $this->getSymfonyEnv(),
            $output
        );

        return $this->getSymfonyRemoteConsoleExec()->getLastReturnStatus();
    }
}
