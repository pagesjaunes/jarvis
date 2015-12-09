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

class InstallDependenciesAndAssetsCommand extends BaseSymfonyCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Install dependencies with composer and build assets.');
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        dump($this->getSymfonyEnvs());
        $returnStatus = 0;
        foreach ($this->getSymfonyEnvs() as $symfonyEnv) {
            $returnStatus = $this->composerInstall($projectName, $symfonyEnv, $output);

            if (0 == $returnStatus) {
                $returnStatus = $this->cacheWarmup($projectName, $symfonyEnv, $output);
            }

            if (0 == $returnStatus) {
                $returnStatus = $this->buildAssets($projectName, $symfonyEnv, $output);
            }
        }

        return $returnStatus;
    }

    protected function composerInstall($projectName, $symfonyEnv, OutputInterface $output)
    {
        $parameters = [
            '--project-name' => $projectName,
        ];

        if ($symfonyEnv == 'prod') {
            $parameters['--optimize-autoloader'] = true;
            $parameters['--prefer-dist'] = true;
            $parameters['--no-dev'] = true;
        }

        return $this->getApplication()->executeCommand('project:composer:install', $parameters, $output);
    }

    protected function cacheWarmup($projectName, $symfonyEnv, OutputInterface $output)
    {
        return $this->getApplication()->executeCommand('project:symfony:cache:warmup', [
            '--project-name' => $projectName,
            '--symfony-env' => [$symfonyEnv],
        ], $output);
    }

    protected function buildAssets($projectName, $symfonyEnv, OutputInterface $output)
    {
        return $this->getApplication()->executeCommand('project:assets:build', [
            '--project-name' => $projectName,
            '--symfony-env' => [$symfonyEnv],
        ], $output);
    }
}
