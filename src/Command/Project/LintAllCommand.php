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

class LintAllCommand extends BaseCommand
{
    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Lints a template and outputs encountered errors');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->getApplication()->executeCommand('project:lint:php', [
                '--project-name' => $projectName
            ], $output);

        $this->getApplication()->executeCommand('project:lint:twig', [
                '--project-name' => $projectName
            ], $output);

        $this->getApplication()->executeCommand('project:lint:yaml', [
                '--project-name' => $projectName
            ], $output);

            // $this->getApplication()->executeCommand('project:lint:scss', [
            //     '--project-name' => $projectName
            // ], $output);
    }
}
