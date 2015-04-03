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
use Symfony\Component\Console\Output\OutputInterface;

class ServiceCommand extends BaseCommand
{
    use \Jarvis\Ssh\SshExecAwareTrait;

    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var string
     */
    private $serviceCommandName;

    /**
     * Sets the value of serviceName.
     *
     * @param string $serviceName the service name
     *
     * @return self
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Sets the value of serviceCommandName.
     *
     * @param string $serviceCommandName the service command name
     *
     * @return self
     */
    public function setServiceCommandName($serviceCommandName)
    {
        $this->serviceCommandName = $serviceCommandName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Runs a System V init script in virtual machine');

        if ($this->getName() === 'vm:service') {
            $this->addArgument(
                'service_name',
                InputArgument::REQUIRED,
                'Service name'
            );

            $this->addArgument(
                'command_name',
                InputArgument::REQUIRED,
                'Command name'
            );
        }
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('service_name')) {
            $this->serviceName = $input->getArgument('service_name');
        }

        if ($input->hasArgument('command_name')) {
            $this->serviceCommandName = $input->getArgument('command_name');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ('status' == $this->serviceCommandName) {
            $this->getSshExec()->exec(
                sprintf(
                    'sudo service %s %s',
                    $this->serviceName,
                    $this->serviceCommandName
                )
            );

            return $this->getSshExec()->getLastReturnStatus();
        }

        $this->getSshExec()->run(
            sprintf(
                'sudo service %s %s',
                $this->serviceName,
                $this->serviceCommandName
            ),
            $output
        );

        if ($this->getSshExec()->getLastReturnStatus() == 0) { // EXIT_SUCCESS
            switch ($this->serviceCommandName) {
                case 'start':
                    $output->writeln(sprintf(
                        '<info>Service "<comment>%s</comment>" is started in virtual machine</info>',
                        $this->serviceName
                    ));
                    break;
                case 'stop':
                    $output->writeln(sprintf(
                        '<info>Service "<comment>%s</comment>" is stopped in virtual machine</info>',
                        $this->serviceName
                    ));
                    break;
                case 'restart':
                    $output->writeln(sprintf(
                        '<info>Service "<comment>%s</comment>" is restarted in virtual machine</info>',
                        $this->serviceName
                    ));
                    break;
                default:
                    # code...
                    break;
            }
        }

        return $this->getSshExec()->getLastReturnStatus();
    }
}
