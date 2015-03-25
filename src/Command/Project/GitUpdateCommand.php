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
use Jarvis\Process\CommandExistTrait;
use Jarvis\Process\ExecAwareTrait;
use Jarvis\Project\ProjectConfiguration;

class GitUpdateCommand extends BaseCommand
{
    use ExecAwareTrait;

    use CommandExistTrait;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Updates all branches that are tracking a remote branch for to one or all projects, install dependencies with composer and build assets.');

        $this->addOption('install-git-up-if-not-installed', null, InputOption::VALUE_REQUIRED, 'Checks for installed git up command and if not installed install PyGitUp (https://github.com/msiemens/PyGitUp).');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->installGitUpIfNotInstalled = $input->getOption('install-git-up-if-not-installed');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $returnStatus = $this->updateGitProject($projectConfig, $output);

        if (0 == $returnStatus) {
            $returnStatus = $this->composerInstall($projectName, $output);
        }

        if (0 == $returnStatus) {
            $returnStatus = $this->buildAssets($projectName, $output);
        }

        return $returnStatus;
    }

    protected function composerInstall($projectName, OutputInterface $output)
    {
        $this->getApplication()->executeCommand('project:composer:install', [
            '--project-name' => $projectName
        ], $output);
    }

    protected function buildAssets($projectName, OutputInterface $output)
    {
        return $this->getApplication()->executeCommand('project:assets:build', [
            '--project-name' => $projectName
        ], $output);
    }

    protected function updateGitProject(ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        if ($this->installGitUpIfNotInstalled && !$this->commandExist('git-up')) {
            $this->installCommandGitUp($output);
        }

        $output->writeln(
            sprintf(
                '<comment>Update git project "<info>%s</info>"</comment>',
                $projectConfig->getProjectName()
            )
        );

        $this->getExec()->run('git up', null, $projectConfig->getLocalGitRepositoryDir());

        return $this->getExec()->getLastReturnStatus();
    }

    protected function installCommandGitUp(OutputInterface $output)
    {
        // Il faut utiliser l'option --user pour forcer l'installation en mode user only
        $output->writeln('<error>git up command doesn\'t exist.</error>');
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you want to install git up?', false);

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $this->getExec()->run('pip install --user git-up', $output, $projectConfig->getLocalGitRepositoryDir());

        if (0 == $this->getExec()->getLastReturnStatus()) {
            $output->writeln('<comment>Adding a path to the .bashrc file</comment>');
            $output->writeln('export PATH=$PATH:$HOME/.local/bin');
        }

        return $this->getExec()->getLastReturnStatus();
    }
}
