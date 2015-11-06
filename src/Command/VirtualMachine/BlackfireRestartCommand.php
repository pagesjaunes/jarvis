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

class BlackfireRestartCommand extends BaseCommand
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
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->run('sudo /etc/init.d/blackfire-agent restart', $output);
        }
        if ($this->getSshExec()->getLastReturnStatus() == 0) {
            $this->getSshExec()->run('sudo service php5-fpm restart', $output);
        }

        return $this->getSshExec()->getLastReturnStatus();
    }
}
