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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BlackfireInstallCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Installing Blackfire');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getSshExec()->run('wget -O - https://packagecloud.io/gpg.key | sudo apt-key add -', $output);
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->run('echo "deb http://packages.blackfire.io/debian any main" | sudo tee /etc/apt/sources.list.d/blackfire.list', $output);
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->run('sudo apt-get update', $output);
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->run('sudo apt-get install blackfire-agent', $output);
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->exec('sudo blackfire-agent -config="/etc/blackfire/agent" -register');
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->exec('sudo blackfire-agent  -config="/etc/blackfire/agent" -d');
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->run('sudo /etc/init.d/blackfire-agent restart', $output);
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->exec('blackfire config');
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->run('sudo apt-get install blackfire-php', $output);
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getApplication()->executeCommand('vm:service:php-fpm:restart', [], $output);
        }

        return $this->getSshExec()->getLastReturnStatus();
    }
}
