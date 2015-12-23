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

namespace Jarvis\Project\Repository;

use Jarvis\Project\ProjectConfiguration;

interface ProjectConfigurationRepository
{
    /**
     * @return integer
     */
    public function count();

    /**
     * @param  string $projectName
     *
     * @return boolean
     */
    public function has($projectName);

    /**
     * @param  string $projectName
     *
     * @return null|ProjectConfiguration
     */
    public function find($projectName);

    /**
     * @return []|ProjectConfiguration[]
     */
    public function findAll();

    /**
     * @param array $data
     */
    public function add(array $data);

    /**
     * @param array $data
     */
    public function remove(ProjectConfiguration $configuration);

    /**
     * @return array
     */
    public function getProjectNames();

   /**
     * @return array
     */
    public function getProjectAlreadyInstalledNames();
}
