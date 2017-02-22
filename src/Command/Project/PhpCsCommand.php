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

class PhpCsCommand extends BaseBuildCommand
{
    use \Jarvis\Process\PhpCsFixerAwareTrait;

    private $fixCodingStandardProblems = false;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Check syntax the php files of sourcecode files');

        $this->addOption('fix', null, InputOption::VALUE_NONE, 'Tries to fix as much coding standards problems as possible.');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->fixCodingStandardProblems = $input->getOption('fix');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(sprintf(
            '<comment>%s for project "<info>%s</info>"</comment>',
            $this->getDescription(),
            $projectName
        ));

        return $this->getPhpCsFixer()->fixRemoteDir(
            $projectConfig->getRemoteWebappDir(),
            $output,
            [
                'dry-run' => !$this->fixCodingStandardProblems
            ]
        );
    }
}
