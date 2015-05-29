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
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @see https://raw.githubusercontent.com/gushphp/gush/a674121999bc8cb3d4446c1db54cd798a0e062c5/src/Command/Core/AutocompleteCommand.php
 */
class AutocompleteCommand extends Command
{
    private $cacheDir;
    private $runningFile;

    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:autocomplete')
            ->setDescription('Create file for Command-line completion')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> creates a script to active Command-line completion for Jarvis commands in Bash:

    <info>$ %command.full_name%</info>

To enable Bash autocomplete, run the following command,
or add the following line to the ~/.bash_profile or ~/.bashrc file:

    <info>$ source $(%command.full_name%)</info>

EOF
            )
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'It is the name of the command for which to add a completion',
                'jarvis'
            )
        ;
    }

    /**
     * Sets the value of cacheDir.
     *
     * @param mixed $cacheDir the cache dir
     *
     * @return self
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->runningFile = realpath($_SERVER['argv'][0]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $buffer = new BufferedOutput();
        (new DescriptorHelper())->describe(
            $buffer,
            $this->getApplication(),
            ['format' => 'json']
        );

        $autocompleteHelper = $this->getHelper('autocomplete');
        $completeFunctionName = sprintf('_jarvis_%s', md5($this->runningFile));
        $completeCommand = $input->getArgument('name');

        $script = $autocompleteHelper->getAutoCompleteScript(
            $completeFunctionName,
            $completeCommand,
            json_decode($buffer->fetch(), true)['commands']
        );

        $scriptFile = $this->getCacheDir().DIRECTORY_SEPARATOR.sprintf('.%s-autocomplete.bash', $input->getArgument('name'));

        $this->getLocalFilesystem()->dumpFile($scriptFile, $script);

        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
            $output->writeln($script);
        }

        $output->write($scriptFile, true);
    }

    protected function getCacheDir()
    {
        if (!$this->cacheDir) {
            $this->cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jarvis';
        }

        $this->getLocalFilesystem()->mkdir($this->cacheDir);

        return $this->cacheDir;
    }
}
