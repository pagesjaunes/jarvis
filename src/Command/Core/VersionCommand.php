<?php

namespace Jarvis\Command\Core;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class VersionCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:version')
            ->setDescription('Display current version')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command displays current version:

  <info>php %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf(
            'Running <comment>%s</comment> version <comment>%s</comment>',
            $this->getApplication()->getName(),
            $this->getApplication()->getVersion()
        ));
    }
}
