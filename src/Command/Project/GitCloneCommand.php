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
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Command\Project\ComposerCommand;
use Jarvis\Command\Project\SymfonyAssetsBuildCommand;
use Jarvis\Project\ProjectConfiguration;

class GitCloneCommand extends BaseGitCommand
{
    /**
     * @var ComposerCommand
     */
    protected $composerInstallCommand;

    /**
     * Sets the composer install command service.
     *
     * @param ComposerCommand $command the composer install command
     *
     * @return self
     */
    public function setComposerInstallCommand(ComposerCommand $command)
    {
        $this->composerInstallCommand = $command;

        return $this;
    }

    /**
     * Sets the build assets command service.
     *
     * @param SymfonyAssetsBuildCommand $command the composer install command
     *
     * @return self
     */
    public function setSymfonyAssetsBuildCommand(SymfonyAssetsBuildCommand $command)
    {
        $this->SymfonyAssetsBuildCommand = $command;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Clones to one or all projects (git clone, updates, removes or installs all the dependencies and build assets)');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled && count($this->getProjectConfigurationRepository()->getProjectNames());
    }

    /**
     * {@inheritdoc}
     */
    protected function getProjectNamesToExclude()
    {
        return $this->getProjectConfigurationRepository()->getProjectAlreadyInstalledNames();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllProjectsConfig()
    {
        return $this->getProjectConfigurationRepository()->findNotInstalled();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllBundlesProjectConfig()
    {
        return new \CallbackFilterIterator(new \ArrayIterator($this->getProjectConfigurationRepository()->findNotInstalled()), function (ProjectConfiguration $projectConfig) {
            return (false !== strpos($projectConfig->getProjectName(), '-bundle'));
        });
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->cloneGitProject($projectName, $projectConfig, $output);

        $this->getApplication()->add($this->composerInstallCommand);
        $this->getApplication()->add($this->SymfonyAssetsBuildCommand);

        $this->getApplication()->executeCommand($this->composerInstallCommand->getName(), [
            '--project-name' => $projectName,
            ($output->isDebug() ? ' -vvv' : '-v')
        ], $output);

        $this->getApplication()->executeCommand($this->SymfonyAssetsBuildCommand->getName(), [
            '--project-name' => $projectName,
            ($output->isDebug() ? ' -vvv' : '-v')
        ], $output);
    }

    protected function cloneGitProject($projectName, ProjectConfiguration $config, OutputInterface $output)
    {
        if (is_dir($config->getLocalGitRepositoryDir())) {
            $output->writeln(
                sprintf(
                    '<comment>A project with that name "<info>%s</info>" already exists</comment>',
                    $projectName
                )
            );

            return;
        }

        $output->writeln(
            sprintf(
                '<comment>Create git project "<info>%s</info>" in "<info>%s</info>"</comment>',
                $projectName,
                $config->getLocalGitRepositoryDir()
            )
        );

        $this->getExec()->run(strtr(
            'git clone %git_repository_url% %local_git_repository_dir% && cd %local_git_repository_dir% && git checkout %git_target_branch%',
            [
                '%local_git_repository_dir%' => $config->getLocalGitRepositoryDir(),
                '%git_repository_url%' => $config->getGitRepositoryUrl(),
                '%git_target_branch%' => $config->getGitTargetBranch()
            ]
        ), $output);
    }
}
