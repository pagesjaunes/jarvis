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

class InstallDependenciesAndAssetsCommand extends BaseCommand
{
    /**
     * @var string
     */
    private $symfonyEnv;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Install dependencies with composer and build assets.');

        $this->addOption('--symfony-env', null, InputOption::VALUE_REQUIRED, 'The Symfony Environment name.', 'dev');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->symfonyEnv = $input->getOption('symfony-env');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $returnStatus = $this->composerInstall($projectName, $output);

        if (0 == $returnStatus) {
            $returnStatus = $this->cacheWarmup($projectName, $output);
        }

        if (0 == $returnStatus) {
            $returnStatus = $this->buildAssets($projectName, $output);
        }

        return $returnStatus;
    }

    protected function composerInstall($projectName, OutputInterface $output)
    {
        $parameters = [
            '--project-name' => $projectName
        ];
        if ($this->symfonyEnv == 'prod') {
            $parameters['--optimize-autoloader'] = true;
            $parameters['--prefer-dist'] = true;
            $parameters['--no-dev'] = true;
        }
        $this->getApplication()->executeCommand('project:composer:install', $parameters, $output);
    }

    protected function cacheWarmup($projectName, OutputInterface $output)
    {
        return $this->getApplication()->executeCommand('project:symfony:cache:warmup', [
            '--project-name' => $projectName,
            $this->symfonyEnv,
        ], $output);
    }

    protected function buildAssets($projectName, OutputInterface $output)
    {
        return $this->getApplication()->executeCommand('project:assets:build', [
            '--project-name' => $projectName
        ], $output);
    }
}
