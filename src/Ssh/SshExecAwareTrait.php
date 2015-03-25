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

use Jarvis\Ssh\Exec as SshExec;

trait SshExecAwareTrait
{
    /**
     * @var SshExec
     */
    private $sshExec;

    /**
     * Gets the value of sshExec.
     *
     * @return SshExec
     */
    public function getSshExec()
    {
        return $this->sshExec;
    }

    /**
     * Sets the value of sshExec.
     *
     * @param SshExec $sshExec the ssh exec
     *
     * @return self
     */
    public function setSshExec(SshExec $sshExec)
    {
        $this->sshExec = $sshExec;

        return $this;
    }
}
