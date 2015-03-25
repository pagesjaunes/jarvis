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

namespace Jarvis\Command\Core;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class CacheClearCommand extends Command
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:container:cache-clear')
            ->setDescription('Clears the cache')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command clears the jarvis cache:

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
        $containerCacheDir = realpath($this->container->getParameter('cache_dir'));

        if (!$containerCacheDir) {
            $output->writeln('<error>Cache directory does not exists</error>');
            return;
        }

        $filesystem = $this->container->get('local.filesystem');

        if (!is_writable($containerCacheDir)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $containerCacheDir));
        }

        $output->writeln('  Removing old cache directory');

        $filesystem->remove($containerCacheDir);
        $filesystem->mkdir($containerCacheDir);

        $output->writeln('  Done');
    }
}
