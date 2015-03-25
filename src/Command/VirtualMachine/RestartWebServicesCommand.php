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

namespace Jarvis\Command\VirtualMachine;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartWebServicesCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Restarts Nginx in virtual machine');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->executeCommand('vm:service', [
            'service_name' => 'varnish',
            'command_name' => 'restart'
        ], $output);

        $this->getApplication()->executeCommand('vm:service', [
            'service_name' => 'php5-fpm',
            'command_name' => 'restart'
        ], $output);

        $this->getApplication()->executeCommand('vm:service', [
            'service_name' => 'nginx',
            'command_name' => 'restart'
        ], $output);
    }
}
