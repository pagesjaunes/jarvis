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
    /**
     * @var string
     */
    private $servicesName = [];

    /**
     * @var string
     */
    private $serviceCommandName;

    /**
     * Sets the value of servicesName.
     *
     * @param string $servicesName the service name
     *
     * @return self
     */
    public function setServiceName($servicesName)
    {
        $this->servicesName = [$servicesName];

        return $this;
    }

    /**
     * Sets the value of servicesName.
     *
     * @param array $servicesNames Many services names
     *
     * @return self
     */
    public function setServicesName(array $servicesNames)
    {
        $this->servicesName = $servicesNames;

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
                'Service name or many service name separated by a comma (eg. nginx,php_fpm,varnish)'
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
            $this->servicesName = array_map('trim', explode(',', $input->getArgument('service_name')));
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
        foreach ($this->servicesName as $serviceName) {
            if ('status' == $this->serviceCommandName) {
                $this->getSshExec()->passthru(
                    sprintf(
                        'sudo service %s %s',
                        $serviceName,
                        $this->serviceCommandName
                    )
                );

                return $this->getSshExec()->getLastReturnStatus();
            }

            $this->getSshExec()->run(
                sprintf(
                    'sudo service %s %s',
                    $serviceName,
                    $this->serviceCommandName
                ),
                $output
            );

            if ($this->getSshExec()->getLastReturnStatus() == 0) { // EXIT_SUCCESS
                switch ($this->serviceCommandName) {
                    case 'start':
                        $output->writeln(sprintf(
                            '<info>Service "<comment>%s</comment>" is started in virtual machine</info>',
                            $serviceName
                        ));
                        break;
                    case 'stop':
                        $output->writeln(sprintf(
                            '<info>Service "<comment>%s</comment>" is stopped in virtual machine</info>',
                            $serviceName
                        ));
                        break;
                    case 'restart':
                        $output->writeln(sprintf(
                            '<info>Service "<comment>%s</comment>" is restarted in virtual machine</info>',
                            $serviceName
                        ));
                        break;
                    default:
                        $output->writeln(sprintf(
                            '<info>The command <comment>%s</comment> is called for the service "<comment>%s</comment>" in virtual machine</info>',
                            $this->serviceCommandName,
                            $serviceName
                        ));
                        break;
                }
            }
        }

        return $this->getSshExec()->getLastReturnStatus();
    }
}
