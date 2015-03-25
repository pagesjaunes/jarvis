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

namespace Jarvis\Symfony;

use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Ssh\Exec as SshExec;

class RemoteConsoleExec
{
    /**
     * @var SshExec
     */
    private $sshExec;

    public function __construct(SshExec $sshExec)
    {
        $this->sshExec = $sshExec;
    }

    /**
     * Executes without output buffering symfony console using ssh .
     *
     * @param  string $symfonyConsolePath
     * @param  string $commandAndOptions Command name and options
     * @param  OutputInterface|null $output
     *
     * @return string
     */
    public function exec($symfonyConsolePath, $commandNameAndOptions, $env, $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE)
    {
        $commandLine = strtr(
            'php %symfony_console_path% %command_name_and_options% --env=%env% %verbosity_option%',
            [
                '%symfony_console_path%' => $symfonyConsolePath,
                '%command_name_and_options%' => $commandNameAndOptions,
                '%env%' => $env,
                '%verbosity_option%' => $this->getVerbosityOption($verbosity)
            ]
        );

        return $this->sshExec->exec($commandLine);
    }

    /**
     * Runs symfony console using ssh .
     *
     * @param  string $symfonyConsolePath
     * @param  string $commandAndOptions Command name and options
     * @param  OutputInterface|null $output
     *
     * @return string
     */
    public function run($symfonyConsolePath, $commandNameAndOptions, $env, OutputInterface $output = null, $verbosityMin = OutputInterface::VERBOSITY_VERY_VERBOSE)
    {
        $verbosityOption = $output ? $this->getVerbosityOption($output->getVerbosity()) : '';
        $commandLine = strtr(
            'php %symfony_console_path% %command_name_and_options% --env=%env% %verbosity_option%',
            [
                '%symfony_console_path%' => $symfonyConsolePath,
                '%command_name_and_options%' => $commandNameAndOptions,
                '%env%' => $env,
                '%verbosity_option%' => $verbosityOption
            ]
        );

        return $this->sshExec->run($commandLine, $output, $verbosityMin);
    }

    /**
     * Return last return status of the last Unix command has been executed
     *
     * @return int
     */
    public function getLastReturnStatus()
    {
        return $this->sshExec->getLastReturnStatus();
    }

    protected function getVerbosityOption($verbosity)
    {
        switch ($verbosity) {
            case OutputInterface::VERBOSITY_QUIET:
                return '--quiet';

            case OutputInterface::VERBOSITY_DEBUG:
                return '-vvv';

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                return '-vv';

            case OutputInterface::VERBOSITY_VERBOSE:
                return '-v';

            default:
                return '';
        }
    }
}
