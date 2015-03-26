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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;

class TestAllCommand extends BaseCommand
{
    /**
     * @var boolean
     */
    private $isTestUnitEnabled;

    /**
     * @var boolean
     */
    private $isTestIntegrationEnabled;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Executing tests unit and integration');

        parent::configure();

        $this->addOption('no-unit', null, InputArgument::REQUIRED);
        $this->addOption('no-integration', null, InputArgument::REQUIRED);
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->isTestUnitEnabled = null === $input->getOption('no-unit');
        $this->isTestIntegrationEnabled = null === $input->getOption('no-integration');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Executes tests for project "<info>%s</info>"</comment>',
                $projectName
            )
        );

        $parameters = [
            '--project-name' => $projectName,
            '--no-display-status-text' => true
        ];

        if ($this->isTestUnitEnabled) {
            $testUnitStatusCode = $this->getApplication()->executeCommand('project:tests:unit', $parameters, $output);
        }

        if ($this->isTestIntegrationEnabled) {
            $testIntegrationStatusCode = $this->getApplication()->executeCommand('project:tests:integration', $parameters, $output);
        }

        $output->writeln(
            sprintf(
                '<comment>Executes unit tests for project "<info>%s</info>"</comment>: %s',
                $projectName,
                $testUnitStatusCode == 0 ?
                    '<info>SUCCESS</info>'
                    :
                    '<error>ERROR</error>'
            )
        );

        $output->writeln(
            sprintf(
                '<comment>Executes integration tests for project "<info>%s</info>"</comment>: %s',
                $projectName,
                $testIntegrationStatusCode == 0 ?
                    ' <info>SUCCESS</info>'
                    :
                    ' <error>ERROR</error>'
            )
        );

        return $testUnitStatusCode + $testIntegrationStatusCode;
    }
}
