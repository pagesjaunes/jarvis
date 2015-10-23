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

namespace Jarvis\Command\Project;

abstract class BaseBuildCommand extends BaseCommand
{
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;
    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;
    use \Jarvis\Process\ExecAwareTrait;
    use \Jarvis\Ssh\SshExecAwareTrait;

    /**
     * @var string
     */
    private $remoteBuildDir;

    /**
     * @var string
     */
    private $localBuildDir;

    /**
     * Sets the value of remoteBuildDir.
     *
     * @param string $remoteBuildDir the remote build dir
     *
     * @return self
     */
    public function setRemoteBuildDir($remoteBuildDir)
    {
        $this->remoteBuildDir = $remoteBuildDir;

        return $this;
    }

    /**
     * Sets the value of localBuildDir.
     *
     * @param string $localBuildDir the local build dir
     *
     * @return self
     */
    public function setLocalBuildDir($localBuildDir)
    {
        $this->localBuildDir = $localBuildDir;

        return $this;
    }

    /**
     * Gets the value of remoteBuildDir.
     *
     * @return string
     */
    public function getRemoteBuildDir()
    {
        if (null === $this->remoteBuildDir) {
            throw new \LogicException('Local build directory is not defined');
        }

        return $this->remoteBuildDir;
    }

    /**
     * Gets the value of localBuildDir.
     *
     * @return string
     */
    public function getLocalBuildDir()
    {
        if (null === $this->localBuildDir) {
            throw new \LogicException('Local build directory is not defined');
        }

        return $this->localBuildDir;
    }

    public function openFile($filepath)
    {
        $this->getExec()->exec(strtr(
            'which xdg-open && xdg-open %file% || which open && open %file%',
            [
                '%file%' => $filepath
            ]
        ));
    }
}
