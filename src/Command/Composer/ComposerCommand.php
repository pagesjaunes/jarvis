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

namespace Jarvis\Command\Composer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerCommand extends Command
{
    use \Jarvis\Ssh\SshExecAwareTrait;

    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    /**
     * @var bool
     */
    private $enabled = false;

    /**
     * @var string
     */
    private $commandName;

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
        return $this->enabled;
    }

    /**
     * Sets the value of commandName.
     *
     * @param string $commandName the command name
     *
     * @return self
     */
    public function setCommandName($commandName)
    {
        $this->commandName = $commandName;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>'.$this->getDescription().'</comment>');

        $commandLine = strtr(
            'sudo composer %command_name% %command_arguments% %command_options%',
            [
                '%command_name%' => $this->commandName,
                '%command_arguments%' => implode(' ', $this->getCommandArguments($input)),
                '%command_options%' => implode(' ', $this->getCommandOptions($input)),
            ]
        );

        $report = $this->getSshExec()->exec($commandLine);

        $report = str_replace('composer', $this->getName(), $report);

        $output->writeln(
            $report,
            OutputInterface::OUTPUT_RAW
        );

        return $this->getSshExec()->getLastReturnStatus() == 0;
    }

    protected function getCommandArguments($input)
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

    protected function getCommandOptions($input)
    {
        $commandOptions = [];
        foreach ($input->getOptions() as $name => $value) {
            if ($value === false) {
                continue;
            }

            switch ($name) {
                case 'verbose':
                case 'profile':
                case 'working-dir':
                case 'project-name':
                case 'cache-dir':
                case 'jarvis-extension-autoload-dir':
                    break;
                default:
                    if ($value === true) {
                        $commandOptions[] = sprintf('--%s', $name);
                        continue;
                    }

                    $commandOptions[] = sprintf('--%s=%s', $name, $value);
                    break;
            }
        }

        return $commandOptions;
    }
}
