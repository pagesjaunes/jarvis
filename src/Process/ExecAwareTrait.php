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

trait ExecAwareTrait
{
    /**
     * @var Exec
     */
    private $exec;

    /**
     * Gets the value of exec.
     *
     * @return Exec
     */
    public function getExec()
    {
        if (null == $this->exec) {
            throw new \LogicException('The service "exec" is not initialize');
        }
        return $this->exec;
    }

    /**
     * Sets the value of exec.
     *
     * @param Exec $exec the exec
     *
     * @return self
     */
    public function setExec(Exec $exec)
    {
        $this->exec = $exec;

        return $this;
    }
}
