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

namespace Jarvis\Ssh;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Exec
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    private $options;

    /**
     * @var null|int
     */
    private $lastReturnStatus;

    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired([
            'ssh_user',
            'ssh_host',
            'ssh_port',
            'ssh_identity_file',
        ]);

        $this->options = $resolver->resolve($options);
    }

    /**
     * Gets the option.
     *
     * @return string
     */
    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" option does not exist.', $name));
        }

        return $this->options[$name];
    }

    /**
     * Returns true if an option exists by name.
     *
     * @param string $name The option name
     *
     * @return bool true if the option object exists, false otherwise
     */
    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    public function checkStatus(OutputInterface $output = null)
    {
        $status = trim($this->getStatus());

        if ('ok' == $status) {
            return true;
        }

        $message = $status;
        if (false !== strpos($status, 'Permission denied')) {
            $message = 'no auth';
        }

        $message = sprintf(
            '<error>%s %s %s</error>',
            $this->getOption('ssh_user'),
            $this->getOption('ssh_host'),
            $message
        );
        if ($output instanceof OutputInterface) {
            $output->writeln();
        } else {
            throw new \RuntimeException($message);
        }
    }

    /**
     * Get the connection status.
     *
     */
    public function getStatus()
    {
        $commandLine = strtr(
            'ssh %user%@%host% -p %port% %identity_file_option% -o BatchMode=yes -o ConnectTimeout=5 echo ok 2>&1',
            [
                '%user%' => $this->getOption('ssh_user'),
                '%host%' => $this->getOption('ssh_host'),
                '%port%' => $this->getOption('ssh_port'),
                '%identity_file_option%' => $this->getOption('ssh_identity_file') ? '-i '.$this->getOption('ssh_identity_file') : '',
            ]
        );

        !$this->logger ?: $this->logger->debug($commandLine);

        ob_start();
        passthru($commandLine, $returnStatus);
        return ob_get_clean();
    }

    /**
     * Execute an external program
     *
     * @param  string $commandLine
     * @param null|string $cwd The working directory
     */
    public function passthru($commandLine, $cwd = null)
    {
        $commandLine = $this->getCommandLineWithSsh($commandLine);

        !$this->logger ?: $this->logger->debug($commandLine);

        passthru($commandLine, $returnStatus);

        if ($returnStatus != 0) {
            $this->checkStatus();
        }

        $this->lastReturnStatus = $returnStatus;
    }

    /**
     * Execute an external program using SSH.
     *
     * @param  string $commandLine
     *
     * @return array  The array will be filled with every line of output from the command
     */
    public function exec($commandLine)
    {
        $commandLine = $this->getCommandLineWithSsh($commandLine);

        !$this->logger ?: $this->logger->debug($commandLine);

        exec($commandLine, $output, $returnStatus);

        if ($returnStatus != 0) {
            $this->checkStatus();
        }

        $this->lastReturnStatus = $returnStatus;

        return implode(PHP_EOL, $output);
    }

    /**
     * Runs command using SSH.
     *
     * @param string               $commandLine
     * @param OutputInterface|null $output
     *
     * @return string
     */
    public function run($commandLine, OutputInterface $output = null, $verbosityMin = OutputInterface::VERBOSITY_VERY_VERBOSE)
    {
        $commandLine = $this->getCommandLineWithSsh($commandLine);

        !$this->logger ?: $this->logger->debug($commandLine);

        ob_start();

        passthru($commandLine, $returnStatus);

        if ($output instanceof OutputInterface) {
            if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET || $verbosityMin == OutputInterface::VERBOSITY_QUIET) {
                // do nothing
            } elseif ($returnStatus != 0) { // execute with error
                $output->writeln('<error>'.trim(ob_get_contents()).'</error>');
            } elseif ($output->getVerbosity() >= $verbosityMin) {
                $output->writeln(trim(ob_get_contents()), OutputInterface::OUTPUT_RAW);
            }
        }

        if ($returnStatus != 0) { // execute with error
            if (!$this->logger) {
                throw new \RuntimeException(trim(ob_get_contents()));
            } else {
                $this->logger->error(trim(ob_get_contents()));
            }
            $this->checkStatus();
        }

        $this->lastReturnStatus = $returnStatus;

        return ob_get_clean();
    }

    /**
     * Return last return status of the last Unix command has been executed.
     *
     * @return int
     */
    public function getLastReturnStatus()
    {
        return $this->lastReturnStatus;
    }

    protected function getCommandLineWithSsh($commandLine)
    {
        return strtr(
            'ssh %user%@%host% -p %port% %identity_file_option% -t -q "%command_line%"', [
            '%command_line%' => $commandLine,
            '%user%' => $this->getOption('ssh_user'),
            '%host%' => $this->getOption('ssh_host'),
            '%port%' => $this->getOption('ssh_port'),
            '%identity_file_option%' => $this->getOption('ssh_identity_file') ? '-i '.$this->getOption('ssh_identity_file') : '',
        ]);
    }
}
