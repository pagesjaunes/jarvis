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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;
use Jarvis\Project\Repository\ProjectConfigurationRepository;

abstract class BaseCommand extends Command
{
    use AskProjectNameTrait;

    const EXIT_SUCCESS = 0;
    const EXIT_ERROR = 0;

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
     * @return ProjectConfigurationRepository
     */
    protected function getProjectConfigurationRepository()
    {
        if (null === $this->projectConfigurationRepository) {
            throw new \RuntimeException('The project configuration repository service does not injected.');
        }

        return $this->projectConfigurationRepository;
    }

    /**
     * @param  string $projectName
     *
     * @return Jarvis\Project\ProjectConfiguration
     */
    protected function getProjectConfiguration($projectName)
    {
        if (!$this->getProjectConfigurationRepository()->has($projectName)) {
            $projectName = $this->getAlternativeProjectName($projectName);
            if ($projectName === null) {
                throw new \InvalidArgumentException(sprintf(
                    'This project "%s" is not configured',
                    $projectName
                ));
            }
        }

        $projectConfig = $this->getProjectConfigurationRepository()->find($projectName);

        if (!$projectConfig) {
            throw new \InvalidArgumentException(sprintf(
                'This project "%s" is not configured',
                $projectName
            ));
        }

        return $projectConfig;
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption(
            'project-name',
            'p',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'The project name or many project names'
        );
        $this->addOption(
            'all-projects',
            null,
            InputOption::VALUE_NONE,
            'Apply the command to all projects'
        );
        $this->addOption(
            'all-bundles',
            null,
            InputOption::VALUE_NONE,
            'Apply the command to all bundles'
        );
    }

    /**
     * Gets project names configured to exclude.
     */
    protected function getProjectNamesToExclude()
    {
        return $this->getProjectConfigurationRepository()->getProjectNotAlreadyInstalledNames();
    }

    /**
     * Gets all project names configured.
     */
    protected function countProjects()
    {
        return $this->getProjectConfigurationRepository()->count();
    }

    protected function getFirstProjectName()
    {
        return $this->getProjectConfigurationRepository()->getProjectNames()[0];
    }

    /**
     * Gets all project names configured.
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
        return new \CallbackFilterIterator(new \ArrayIterator($this->getProjectConfigurationRepository()->findInstalled()), function (ProjectConfiguration $projectConfig) {
            return (false !== strpos($projectConfig->getProjectName(), '-bundle'));
        });
    }

    /**
     * {@inheritdoc}
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

        // Many projects names given
        if (count($input->getOption('project-name')) > 1) {
            foreach ($input->getOption('project-name') as $projectName) {
                $projectConfig = $this->getProjectConfiguration($projectName);

                $statusCode += $this->executeCommandByProject(
                    $projectName,
                    $projectConfig,
                    $output
                );
            }

            return $statusCode;
        }

        $projectName = $this->getCurrentProjectName($input, $output);
        $projectConfig = $this->getProjectConfiguration($projectName);

        return $this->executeCommandByProject($projectName, $projectConfig, $output);
    }

    /**
     * Retrieves current project name.
     *
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getCurrentProjectName(InputInterface $input, OutputInterface $output)
    {
        if (!empty($input->getOption('project-name'))) {
            $projectName = is_array($input->getOption('project-name')) ?
                $input->getOption('project-name')[0]
                :
                $input->getOption('project-name');

            if (!$this->getProjectConfigurationRepository()->has($projectName)) {

                $projectName = $this->getAlternativeProjectName($projectName);
                if ($projectName === null) {
                    throw new \InvalidArgumentException(sprintf(
                        'This project "%s" is not configured',
                        $projectName
                    ));
                }
            }

            return $projectName;
        }

        if (isset($_SERVER['JARVIS_SYMFONY_PROJECT'])) {
            return $_SERVER['JARVIS_SYMFONY_PROJECT'];
        }

        // project name deducted from current directory if option "project-name" and variable environment "JARVIS_SYMFONY_PROJECT" are not used.
        $currentProjectName = isset($_SERVER['PWD']) ? basename($_SERVER['PWD']) : null;
        if (!empty($currentProjectName) && $this->getProjectConfigurationRepository()->has($currentProjectName)) {
            return $currentProjectName;
        }

        if ($this->countProjects() == 1) {
            return $this->getFirstProjectName();
        }

        return $this->askProjectName(
            $output,
            $this->getAllProjectNames(),
            $this->getProjectNamesToExclude()
        );
    }

    /**
     * @param  string $input
     *
     * @return array
     */
    protected function getAlternativeProjectNames($input)
    {
        $alternatives = [];
        foreach ($this->getAllProjectNames() as $projectName) {
            $lev = levenshtein($input, $projectName);
            if ($lev <= strlen($input) / 3 || false !== strpos($projectName, $input)) {
                $alternatives[] = $projectName;
            }
        }

        return $alternatives;
    }

    /**
     * @param  string $input
     *
     * @return null|string
     */
    protected function getAlternativeProjectName($input)
    {
        $alternatives = $this->getAlternativeProjectNames($input);

        if (count($alternatives) == 0) {
            throw new \InvalidArgumentException(sprintf('No project found with %s', $input));
        }

        if (count($alternatives) > 1) {
            throw new \InvalidArgumentException(sprintf('Many projects found with %s', $input));
        }

        return $alternatives[0];
    }
}
