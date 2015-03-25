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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SshCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('vagrant:ssh');
        $this->setDescription('Connects to machine via SSH');

        $this->addArgument('remote_command', InputArgument::OPTIONAL, 'Execute an SSH command directly');

        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Vagrant virtual machine name', 'default');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $this->isVirtualMachineRunning($input->getOption('name'))) {
            $output->writeln('<info>The virtual machine is stopped</info>');

            return;
        }

        $this->getVagrantExec()->ssh($input->getArgument('remote_command'));
    }
}
