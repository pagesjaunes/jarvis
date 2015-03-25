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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Command\Project\AskProjectNameTrait;
use Jarvis\Project\ProjectConfiguration;
use Jarvis\Project\Repository\ProjectConfigurationRepository;

abstract class BaseCommand extends Command
{
    use AskProjectNameTrait;

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var ProjectConfigurationRepository
     */
    private $projectConfigurationRepository;

    /**
     * @param bool $bool
     */
    public function setEnabled($bool)
    {
        $this->enabled = $bool;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled && count($this->projectConfigurationRepository->getProjectInstalledNames());
    }

    /**
     * @param ProjectConfigurationRepository $repository
     */
    public function setProjectConfigurationRepository(ProjectConfigurationRepository $repository)
    {
        $this->projectConfigurationRepository = $repository;
    }
    /**
     * @return string
     */
    protected function getProjectConfigurationRepository()
    {
        if (null == $this->projectConfigurationRepository) {
            throw new \RuntimeException('The project configuration repository service does not injected.');
        }

        return $this->projectConfigurationRepository;
    }

    /**
     * @param string          $projectName
     * @param Configuration   $projectConfig
     * @param OutputInterface $output
     *
     * @return int The command exit code
     */
    abstract protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output);

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('project-name', null, InputOption::VALUE_OPTIONAL, 'The project name');
        $this->addOption('all-projects', null, InputOption::VALUE_NONE, 'Apply the command to all projects');
        $this->addOption('all-bundles', null, InputOption::VALUE_NONE, 'Apply the command to all bundles');
    }

    /**
     * Gets project names configured to exclude
     */
    protected function getProjectNamesToExclude()
    {
        return $this->getProjectConfigurationRepository()->getProjectAlreadyInstalledNames();
    }

    /**
     * Gets all project names configured
     */
    protected function getAllProjectNames()
    {
        return $this->getProjectConfigurationRepository()->getProjectNames();
    }

    /**
     * @return array
     */
    protected function getAllProjectsConfig()
    {
        return $this->getProjectConfigurationRepository()->findInstalled();
    }

    /**
     * @return Traversable|array
     */
    protected function getAllBundlesProjectConfig()
    {
        return new \CallbackFilterIterator(new \ArrayIterator($this->getProjectConfigurationRepository()->findInstalled()), function ($projectConfig) {
            return (false !== strpos($projectConfig->getProjectName(), '-bundle'));
        });
    }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $statusCode = 0;

        if ($input->hasOption('all-projects') && true === $input->getOption('all-projects')) {
            foreach ($this->getAllProjectsConfig() as $projectConfig) {
                $statusCode += $this->executeCommandByProject(
                    $projectConfig->getProjectName(),
                    $projectConfig,
                    $output
                );
            }

            return $statusCode;
        }

        if ($input->hasOption('all-bundles') && true === $input->getOption('all-bundles')) {
            foreach ($this->getAllBundlesProjectConfig() as $projectConfig) {
                $statusCode += $this->executeCommandByProject(
                    $projectConfig->getProjectName(),
                    $projectConfig,
                    $output
                );
            }

            return $statusCode;
        }

        $projectName = $input->getOption('project-name') ?
            $input->getOption('project-name')
            :
            $this->askProjectName($output, $this->getAllProjectNames(), $this->getProjectNamesToExclude())
        ;

        $projectConfig = $this->getProjectConfigurationRepository()->find($projectName);
        if (!$projectConfig) {
            throw new \InvalidArgumentException(sprintf('This project "%s" is not configured', $projectName));
        }

        return $this->executeCommandByProject($projectName, $projectConfig, $output);
    }
}
