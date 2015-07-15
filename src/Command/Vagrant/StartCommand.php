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

namespace Jarvis\Command\Vagrant;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Vagrant\Configuration\SshConfiguration;

class StartCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('vagrant:start');
        $this->setDescription('Starts and provisions the virtual machine');

        $this->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Vagrant provider');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Vagrant virtual machine name', 'default');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Shut it down forcefully virtual machine name before start it');
        $this->addOption('no-ssh-add-private-key', null, InputOption::VALUE_NONE, '');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->isVirtualMachineRunning($input->getOption('name'))) {
            $output->writeln('<info>The virtual machine already started</info>');

            return;
        }

        if ($input->hasOption('force')) {
            $this->getVagrantExec()->exec('halt', $output);
        }

        $commandLine = 'up';
        if (null !== $input->getOption('provider')) {
            $commandLine .= sprintf(
                ' --provider=%s',
                $input->getOption('provider')
            );
        }
        $this->getVagrantExec()->exec($commandLine, $output);

        if (false === $input->getOption('no-ssh-add-private-key')) {
            $this->getApplication()->executeCommand(
                'vagrant:ssh-add-private-key',
                [],
                $output
            );
        }
    }
}
