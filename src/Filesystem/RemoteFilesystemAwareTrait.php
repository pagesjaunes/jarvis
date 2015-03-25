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

namespace Jarvis\Filesystem;

use Jarvis\Filesystem\RemoteFilesystem;

trait RemoteFilesystemAwareTrait
{
    /**
     * @var RemoteFilesystem
     */
    private $remoteFilesystem;

    /**
     * Sets the value of remoteFilesystem.
     *
     * @param RemoteFilesystem $remoteFilesystem the remote filesystem
     *
     * @return self
     */
    public function setRemoteFilesystem(RemoteFilesystem $remoteFilesystem)
    {
        $this->remoteFilesystem = $remoteFilesystem;

        return $this;
    }

    /**
     * Gets the value of remoteFilesystem.
     *
     * @return RemoteFilesystem
     */
    public function getRemoteFilesystem()
    {
        return $this->remoteFilesystem;
    }
}
