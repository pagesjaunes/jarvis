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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;

class SyncRemoteToLocalCommand extends BaseCommand
{
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Synchronize remote to local directory.');

        $this->addArgument('remote_dir', InputArgument::REQUIRED, 'Remote directory');
        $this->addArgument('local_dir', InputArgument::REQUIRED, 'Remote directory');

        $this->addOption('delete', null, InputOption::VALUE_NONE, 'Whether to delete files that are not in the source directory (defaults to false)');

        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would have been transferred');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getLocalFilesystem()->mkdir($input->getArgument('local_dir'));

        return $this->getRemoteFilesystem()->syncRemoteToLocal(
            $input->getArgument('remote_dir'),
            $input->getArgument('local_dir'),
            [
                'delete' => $input->getOption('delete'),
                'dry_run' => $input->getOption('dry-run'),
            ]
        );
    }
}
