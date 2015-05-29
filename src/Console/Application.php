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

namespace Jarvis\Console;

use KevinGH\Amend;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Console\Helper;

class Application extends BaseApplication
{
    const LOGO = '   ______
             _        _                   _     _          _        _         _
            /\ \     / /\                /\ \  /\ \    _ / /\      /\ \      / /\
            \ \ \   / /  \              /  \ \ \ \ \  /_/ / /      \ \ \    / /  \
            /\ \_\ / / /\ \            / /\ \ \ \ \ \ \___\/       /\ \_\  / / /\ \__
           / /\/_// / /\ \ \          / / /\ \_\/ / /  \ \ \      / /\/_/ / / /\ \___\
  _       / / /  / / /  \ \ \        / / /_/ / /\ \ \   \_\ \    / / /    \ \ \ \/___/
 /\ \    / / /  / / /___/ /\ \      / / /__\/ /  \ \ \  / / /   / / /      \ \ \
 \ \_\  / / /  / / /_____/ /\ \    / / /_____/    \ \ \/ / /   / / /   _    \ \ \
 / / /_/ / /  / /_________/\ \ \  / / /\ \ \       \ \ \/ /___/ / /__ /_/\__/ / /
/ / /__\/ /  / / /_       __\ \_\/ / /  \ \ \       \ \  //\__\/_/___\\ \/___/ /
\/_______/   \_\___\     /____/_/\/_/    \_\/        \_\/ \/_________/ \_____\/

';

    /**
     * @{inheritdoc}
     */
    public function __construct($name = 'Jarvis', $version = '@git-version@')
    {
        // convert errors to exceptions
        set_error_handler(
            function ($code, $message, $file, $line) {
                if (error_reporting() & $code) {
                    throw new \ErrorException($message, 0, $code, $file, $line);
                }
                // @codeCoverageIgnoreStart
            }
            // @codeCoverageIgnoreEnd
        );
        parent::__construct($name, $version);
    }

    /**
     * @{inheritdoc}
     */
    public function getHelp()
    {
        return self::LOGO.parent::getHelp();
    }

    /**
     * @{inheritdoc}
     */
    public function getLongVersion()
    {
        if (('@' . 'git-version@') !== $this->getVersion()) {
            return sprintf(
                '<info>%s</info> version <comment>%s</comment>',
                $this->getName(),
                $this->getVersion()
            );
        }
        return sprintf(
            '<info>%s</info> version <comment>%s</comment> build <comment>%s</comment>',
            $this->getName(),
            $this->getVersion(),
            '@git-commit-short@'
        );
    }

    /**
     * Runs the command.
     *
     * @param string $name A command name or a command alias
     * @param array $parameters An array of parameters
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int The command exit code
     *
     * @throws \Exception
     *
     * @see setCode()
     * @see execute()
     */
    public function executeCommand($name, array $parameters, OutputInterface $output)
    {
        $command = $this->find($name);

        $parameters['command'] = $command;

        return $command->run(
            new ArrayInput($parameters),
            $output
        );
    }

    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        // switch working dir
        if ($newWorkDir = $this->getNewWorkingDir($input)) {
            $oldWorkingDir = getcwd();
            chdir($newWorkDir);

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln(sprintf('Changed CWD to %s', getcwd()));
            }
        }

        if ($input->hasParameterOption('--profile')) {
            $startTime = microtime(true);
        }

        $result = parent::doRun($input, $output);

        if (isset($oldWorkingDir)) {
            chdir($oldWorkingDir);
        }

        if (isset($startTime)) {
            $output->writeln(sprintf(
                'Memory usage: <info>%sMB</info> (peak: <info>%sMB</info>), time: <info>%ss</info>',
                round(memory_get_usage() / 1024 / 1024, 2),
                round(memory_get_peak_usage() / 1024 / 1024, 2),
                round(microtime(true) - $startTime, 2)
            ));
        }

        return $result;
    }

    /**
     * @param  InputInterface    $input
     * @return string
     * @throws \RuntimeException
     */
    private function getNewWorkingDir(InputInterface $input)
    {
        $workingDir = $input->getParameterOption(array('--working-dir', '-d'));

        if (false !== $workingDir && !is_dir($workingDir)) {
            throw new \RuntimeException('Invalid working directory specified.');
        }

        return $workingDir;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('--profile', null, InputOption::VALUE_NONE, 'Display timing and memory usage information'));
        $definition->addOption(new InputOption('--working-dir', '-d', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory.'));
        $definition->addOption(new InputOption('--cache-dir', null, InputOption::VALUE_REQUIRED, 'If specified, use the given directory as cache directory for service container.'));

        return $definition;
    }

   /**
     * @{inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        if (('@' . 'git-version@') !== $this->getVersion()) {
            $command = new Amend\Command('core:update');
            $command->setAliases([
                'self-update'
            ]);
            $command->setManifestUri('@manifest_url@');
            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * @{inheritdoc}
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        if (('@' . 'git-version@') !== $this->getVersion()) {
            $helperSet->set(new Amend\Helper());
        }

        $helperSet->set(new Helper\AutocompleteHelper());

        return $helperSet;
    }
}
