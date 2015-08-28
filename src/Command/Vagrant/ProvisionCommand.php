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

class ProvisionCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('vagrant:provision');
        $this->setDescription('Provisions the vagrant machine');

        $this->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Vagrant provider', 'virtualbox');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getVagrantExec()->run('destroy', $output);

        $options = [];
        if (null !== $input->getOption('provider')) {
            $options['provider'] = $input->getOption('provider');
        }
        $this->getApplication()->executeCommand('vagrant:start', $options, $output);
    }
}
