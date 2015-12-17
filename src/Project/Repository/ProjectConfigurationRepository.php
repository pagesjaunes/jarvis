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
    public function count();

    public function has($projectName);

    public function find($projectName);

    public function findAll();

    public function add(array $data);

    public function remove(ProjectConfiguration $configuration);

    public function getProjectNames();

    public function getProjectAlreadyInstalledNames();
}
