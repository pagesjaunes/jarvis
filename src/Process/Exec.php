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

namespace Jarvis\Process;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;

class Exec
{
    use LoggerAwareTrait;

    /**
     * Execute an external program
     *
     * @param  string $commandLine
     * @param null|string $cwd The working directory
     *
     * @return array  The array will be filled with every line of output from the command
     */
    public function passthru($commandLine, $cwd = null)
    {
        $previousCwd = getcwd();

        if ($cwd) {
            chdir($cwd);

            !$this->logger ?: $this->logger->debug(sprintf('Changed CWD to %s', $cwd));
        }

        !$this->logger ?: $this->logger->debug($commandLine);

        passthru($commandLine, $returnStatus);

        $this->lastReturnStatus = $returnStatus;

        !$previousCwd ?: chdir($previousCwd);
    }

    /**
     * Execute an external program
     *
     * @param  string $commandLine
     * @param null|string $cwd The working directory
     *
     * @return array  The array will be filled with every line of output from the command
     */
    public function exec($commandLine, $cwd = null)
    {
        $previousCwd = getcwd();

        if ($cwd) {
            chdir($cwd);

            !$this->logger ?: $this->logger->debug(sprintf('Changed CWD to %s', $cwd));
        }

        !$this->logger ?: $this->logger->debug($commandLine);

        exec($commandLine, $output, $returnStatus);

        $this->lastReturnStatus = $returnStatus;

        !$previousCwd ?: chdir($previousCwd);

        return $output;
    }

    /**
     * Runs local command.
     *
     * @param  string $commandLine
     * @param  OutputInterface|null $output
     * @param null|string $cwd The working directory
     *
     * @return string
     */
    public function run($commandLine, OutputInterface $output = null, $cwd = null)
    {
        $previousCwd = getcwd();

        if ($cwd) {
            chdir($cwd);

            !$this->logger ?: $this->logger->debug(sprintf('Changed CWD to %s', $cwd));
        }

        !$this->logger ?: $this->logger->debug($commandLine);

        ob_start();
        passthru($commandLine, $returnStatus);
        if ($output instanceof OutputInterface) {
            if ($returnStatus != 0) { // execute with error
                $output->writeln('<error>'.trim(ob_get_contents()).'</error>');
            } elseif ($output->isVerbose()) {
                $output->writeln(ob_get_contents(), OutputInterface::OUTPUT_RAW);
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
     * Return last return status of the last Unix command has been executed
     *
     * @return int
     */
    public function getLastReturnStatus()
    {
        return $this->lastReturnStatus;
    }
}
