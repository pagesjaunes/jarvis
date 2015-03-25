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

trait CheckVirtualMachineStatusTrait
{
    public function isVirtualMachineRunning($name)
    {
        $result = $this->getVagrantExec()->run(sprintf(
            'status %s',
            $name
        ));

        return strpos($result, 'running') !== false;
    }
}
