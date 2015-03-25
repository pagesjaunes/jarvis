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

namespace Jarvis\Command\Vagrant;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Vagrant\Exec as VagrantExec;

class BaseCommand extends Command
{
    use \Jarvis\Process\ExecAwareTrait;
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;
    use \Jarvis\Vagrant\CheckVirtualMachineStatusTrait;

    /**
     * @var bool
     */
    private $enabled = false;

    /**
     * @var VagrantExec
     */
    private $vagrantExec;

    /**
     * @param bool $bool
     */
    public function setEnabled($bool)
    {
        $this->enabled = $bool;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled && $this->isVagrantFileExist();
    }

    /**
     * Sets the value of vagrantExec.
     *
     * @param VagrantExec $vagrantExec the vagrant exec
     *
     * @return self
     */
    public function setVagrantExec(VagrantExec $vagrantExec)
    {
        $this->vagrantExec = $vagrantExec;

        return $this;
    }

    /**
     * Gets the value of vagrantExec.
     *
     * @return VagrantExec
     */
    public function getVagrantExec()
    {
        return $this->vagrantExec;
    }

    protected function isVagrantFileExist()
    {
        $vagrantFilePath = sprintf('%s/Vagrantfile', $this->getVagrantExec()->getCwd());

        // if (!$this->getLocalFilesystem()->exists($vagrantFilePath) && $this->getLocalFilesystem()->exists($vagrantFilePath.'.dist')) {
        //     $this->getLocalFilesystem()->copy($vagrantFilePath.'.dist', $vagrantFilePath);
        // }

        return $this->getLocalFilesystem()->exists($vagrantFilePath);
    }
}
