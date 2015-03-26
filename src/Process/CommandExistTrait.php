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

trait CommandExistTrait
{
    /**
     * Check if command exist.
     *
     * @param string $commandLine
     *
     * @return bool
     */
    protected function commandExist($commandLine, $cwd = null)
    {
        if (null === $cwd) {
            $cwd = $this->getRootDir();
        }

        $previousCwd = getcwd();
        chdir($cwd);

        $returnVal = shell_exec('which '.$commandLine);

        !$previousCwd ?: chdir($previousCwd);

        return null === $returnVal ? false : true;
    }
}
