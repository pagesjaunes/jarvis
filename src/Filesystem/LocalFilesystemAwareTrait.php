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

use Symfony\Component\Filesystem\Filesystem;

trait LocalFilesystemAwareTrait
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Sets the value of filesystem.
     *
     * @param Filesystem $filesystem the remote filesystem
     *
     * @return self
     */
    public function setLocalFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Gets the value of filesystem.
     *
     * @return Filesystem
     */
    public function getLocalFilesystem()
    {
        if (null === $this->filesystem) {
            throw new \RuntimeException('The local filesystem service is not injected');
        }

        return $this->filesystem;
    }
}
