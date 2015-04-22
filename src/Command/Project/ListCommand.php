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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Jarvis\Project\ProjectConfigurationFactory;
use Jarvis\Project\Repository\ProjectConfigurationRepositoryAwareTrait;

/**
 * ListCommand displays the list of all available projects for the application.
 */
class ListCommand extends Command
{
    use ProjectConfigurationRepositoryAwareTrait;

    /**
     * @var ProjectConfigurationFactory
     */
    private $projectConfigurationFactory;

    /**
     * Sets the value of projectConfigurationFactory.
     *
     * @param ProjectConfigurationFactory $projectConfigurationFactory the project configuration factory
     *
     * @return self
     */
    public function setProjectConfigurationFactory(ProjectConfigurationFactory $projectConfigurationFactory)
    {
        $this->projectConfigurationFactory = $projectConfigurationFactory;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Lists projects');
    }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        $output->writeln('Available projects:');

        foreach ($this->getProjectConfigurationRepository()->getProjectNames() as $name) {
            $output->writeln('- <info>'.$name.'</info>');
        }
        $output->writeln('');
    }
}
