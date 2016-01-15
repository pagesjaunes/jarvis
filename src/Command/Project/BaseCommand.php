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
use Jarvis\Search\Fuzzy;

abstract class BaseCommand extends Command
{
    use AskProjectNameTrait;

    const EXIT_SUCCESS = 0;
    const EXIT_ERROR = 1;

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
        return $this->enabled && count($this->getProjectConfigurationRepository()->getProjectInstalledNames());
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
     * @param string          $projectName
     * @param OutputInterface $output
     * @param InputInterface  $input
     *
     * @return Jarvis\Project\ProjectConfiguration
     */
    protected function getProjectConfiguration($projectName, InputInterface $input, OutputInterface $output)
    {
        if (!$this->getProjectConfigurationRepository()->has($projectName)) {
            $projectName = $this->getAlternativeProjectName($projectName, $input, $output);
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
            'project-tag',
            'pt',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'The tag to search for one or many projects.'
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
    protected function getProjectNames()
    {
        $allProjectNames = $this->getAllProjectNames();
        $projectNamesToExclude = $this->getProjectNamesToExclude();

        return count($projectNamesToExclude) ?
            array_values(array_diff($allProjectNames, $projectNamesToExclude))
            :
            $allProjectNames
        ;
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
        $statusCode = self::EXIT_SUCCESS;

        if ($input->hasOption('all-projects') && true === $input->getOption('all-projects')) {
            foreach ($this->getAllProjectsConfig() as $projectConfig) {
                $statusCode += $this->executeCommandByProject(
                    $projectConfig->getProjectName(),
                    $projectConfig,
                    $output
                );
                $output->writeln('');
            }

            return $statusCode;
        }

        if ($input->hasOption('project-tag') &&  count($input->getOption('project-tag')) >= 1) {
            foreach ($this->getProjectConfigurationRepository()->findBy([
                'tags' => $input->getOption('project-tag')
            ]) as $projectConfig) {
                $statusCode += $this->executeCommandByProject(
                    $projectConfig->getProjectName(),
                    $projectConfig,
                    $output
                );
                $output->writeln('');
            }

            return $statusCode;
        }

        // Many projects names given
        if (count($input->getOption('project-name')) > 1) {
            foreach ($input->getOption('project-name') as $projectName) {
                $projectConfig = $this->getProjectConfiguration($projectName, $input, $output);

                $statusCode += $this->executeCommandByProject(
                    $projectName,
                    $projectConfig,
                    $output
                );
                $output->writeln('');
            }

            return $statusCode;
        }

        $projectName = $this->getCurrentProjectName($input, $output);
        $projectConfig = $this->getProjectConfiguration($projectName, $input, $output);

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
                $projectName = $this->getAlternativeProjectName($projectName, $input, $output);
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
            $this->getProjectNames()
        );
    }

    /**
     * @param string $input
     *
     * @return array
     */
    protected function getAlternativeProjectNames($input)
    {
        $alternatives = [];

        $search = new \Jarvis\Ustring\Search();
        foreach ($search->fuzzy($this->getProjectNames(), $input) as $result) {
            $alternatives[] = $result['match'];
        }

        return $alternatives;
    }

    /**
     * @param string          $input
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|string
     */
    protected function getAlternativeProjectName($projectName, InputInterface $input, OutputInterface $output)
    {
        $alternatives = $this->getAlternativeProjectNames($projectName);

        if (count($alternatives) == 0) {
            throw new \InvalidArgumentException(sprintf('No project found with %s', $projectName));
        }

        if (count($alternatives) > 1) {
            return $this->askProjectName(
                $output,
                $alternatives
            );
        }

        return $alternatives[0];
    }
}
