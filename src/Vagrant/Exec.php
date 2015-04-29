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

namespace Jarvis\Vagrant;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;

class Exec
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    private $cwd;

    public function __construct($cwd)
    {
        $this->cwd = $cwd;
    }

    public function ssh($remoteCommandLine = null)
    {
        $previousCwd = getcwd();

        $cwd = realpath($this->cwd);
        !$cwd ?: chdir($this->cwd);

        $commandLine = 'vagrant ssh';

        if (!empty($remoteCommandLine)) {
            $commandLine .= sprintf(' --command "%s" -- -t -q', escapeshellcmd($remoteCommandLine));
        }

        !$this->logger ?: $this->logger->debug($commandLine);

        passthru($commandLine);

        !$previousCwd ?: chdir($previousCwd);
    }

    /**
     * Execute a command using vagrant.
     *
     * @param  string $commandLine
     *
     * @return array  The array will be filled with every line of output from the command
     */
    public function exec($commandLine)
    {
        $commandLine = sprintf('vagrant %s', escapeshellcmd($commandLine));

        $previousCwd = getcwd();

        $cwd = realpath($this->cwd);
        if ($cwd) {
            chdir($this->cwd);
            !$this->logger ?: $this->logger->debug(sprintf('Changed CWD to %s', $cwd));
        }

        !$this->logger ?: $this->logger->debug($commandLine);

        passthru($commandLine, $returnStatus);

        $this->lastReturnStatus = $returnStatus;

        !$previousCwd ?: chdir($previousCwd);
    }

    /**
     * Runs command using vagrant.
     *
     * @param  string $commandLine
     * @param  OutputInterface|null $output
     *
     * @return string
     */
    public function run($commandLine, OutputInterface $output = null, $verbosityMin = OutputInterface::VERBOSITY_VERY_VERBOSE)
    {
        $commandLine = sprintf('vagrant %s', escapeshellcmd($commandLine));

        $previousCwd = getcwd();

        $cwd = realpath($this->cwd);
        if ($cwd) {
            chdir($this->cwd);
            !$this->logger ?: $this->logger->debug(sprintf('Changed CWD to %s', $cwd));
        }

        !$this->logger ?: $this->logger->debug($commandLine);

        ob_start();

        passthru($commandLine, $returnStatus);

        if ($output instanceof OutputInterface) {
            if ($returnStatus != 0) { // execute with error
                $output->writeln('<error>'.trim(ob_get_contents()).'</error>');
            } elseif ($output->getVerbosity() >= $verbosityMin) {
                $output->writeln(trim(ob_get_contents()), OutputInterface::OUTPUT_RAW);
            }
        } elseif ($returnStatus != 0) { // execute with error
            if (!$this->logger) {
                throw new \RuntimeException(trim(ob_get_contents()));
            } else {
                $this->logger->debug(trim(ob_get_contents()));
            }
        }

        $this->lastReturnStatus = $returnStatus;

        !$previousCwd ?: chdir($previousCwd);

        return ob_get_clean();
    }

    /**
     * Gets the value of cwd.
     *
     * @return string
     */
    public function getCwd()
    {
        return $this->cwd;
    }
}
