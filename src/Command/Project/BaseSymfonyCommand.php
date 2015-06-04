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
use Jarvis\Symfony\RemoteConsoleExec as SymfonyRemoteConsoleExec;

abstract class BaseSymfonyCommand extends BaseCommand
{
    use \Jarvis\Symfony\RemoteConsoleExecAwareTrait;

    /**
     * @var string
     */
    private $symfonyEnv;

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
    protected function configure()
    {
        $this->addOption('--symfony-env', null, InputOption::VALUE_REQUIRED, 'The Symfony Environment name.', 'dev');

        parent::configure();
    }

    protected function getSymfonyEnv()
    {
        return $this->symfonyEnv;
    }

    protected function getSymfonyCommandArguments($input)
    {
        $commandArguments = [];
        foreach ($input->getArguments() as $name => $value) {
            switch ($name) {
                case 'command':
                    break;
                default:
                    $commandArguments[] = $value;
                    break;
            }
        }

        return $commandArguments;
    }

    protected function getSymfonyCommandOptions($input)
    {
        $commandOptions = [];
        foreach ($input->getOptions() as $name => $value) {
            if ($value === false) {
                continue;
            }

            switch ($name) {
                case 'symfony-env':
                case 'verbose':
                case 'profile':
                case 'working-dir':
                case 'project-name':
                case 'cache-dir':
                    break;
                default:
                    if ($value === true) {
                        $commandOptions[] = sprintf('--%s', $name);
                        continue;
                    }

                    if (!empty($value)) {
                        $commandOptions[] = sprintf('--%s=%s', $name, $value);
                    }
                    break;
            }
        }

        return $commandOptions;
    }
}
