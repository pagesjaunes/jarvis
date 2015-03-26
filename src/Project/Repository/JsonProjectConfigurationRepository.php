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

use Herrera\Json\Json;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Jarvis\Project\ProjectConfiguration;
use Jarvis\Project\ProjectConfigurationFactory;

class JsonProjectConfigurationRepository implements ProjectConfigurationRepository
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var ProjectConfigurationFactory
     */
    private $projectConfigurationFactory;

    public function __construct($filePath, ProjectConfigurationFactory $projectConfigurationFactory)
    {
        $this->filePath = $filePath;
        $this->projectConfigurationFactory = $projectConfigurationFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function find($projectName)
    {
        return isset($this->getProjectConfigurationCollection()[$projectName]) ?
            $this->getProjectConfigurationCollection()[$projectName]
            :
            null;
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->getProjectConfigurationCollection() as $row) {
            $found = true;

            foreach ($criteria as $name => $value) {
                if ($value !== $accessor->getValue($row, $name)) {
                    $found = false;
                    continue;
                }
            }

            if ($found) {
                return $row;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return $this->getProjectConfigurationCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function findNotInstalled()
    {
        $configs = [];
        foreach ($this->getProjectConfigurationCollection() as $config) {
            if (false === $config->isInstalled()) {
                $configs[] = $config;
            }
        }
        return $configs;
    }

    /**
     * {@inheritDoc}
     */
    public function findInstalled()
    {
        $configs = [];
        foreach ($this->getProjectConfigurationCollection() as $config) {
            if ($config->isInstalled()) {
                $configs[] = $config;
            }
        }
        return $configs;
    }

    /**
     * {@inheritDoc}
     */
    public function add(ProjectConfiguration $configuration)
    {
        $rows = [];
        foreach ($this->getProjectConfigurationCollection() as $row) {
            if ($configuration->getProjectName() == $row->getProjectName()) {
                continue;
            }

            $rows[] = $row;
        }

        $rows[] = $configuration;

        $this->save($rows);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(ProjectConfiguration $configuration)
    {
        $rows = [];
        foreach ($this->getProjectConfigurationCollection() as $row) {
            if ($configuration->getProjectName() == $row->getProjectName()) {
                continue;
            }

            $rows[] = $row;
        }

        $this->save($rows);
    }

    /**
     * {@inheritDoc}
     */
    public function getProjectNames()
    {
        $projectNames = [];

        foreach ($this->getProjectConfigurationCollection() as $config) {
            $projectNames[] = $config->getProjectName();
        }

        return $projectNames;
    }

    /**
     * {@inheritDoc}
     */
    public function getProjectInstalledNames()
    {
        $projectNames = [];

        foreach ($this->getProjectConfigurationCollection() as $config) {
            if ($config->isInstalled()) {
                $projectNames[] = $config->getProjectName();
            }
        }

        return $projectNames;
    }

    /**
     * {@inheritDoc}
     */
    public function getProjectAlreadyInstalledNames()
    {
        $projectNames = [];

        foreach ($this->getProjectConfigurationCollection() as $config) {
            if ($config->isInstalled()) {
                $projectNames[] = $config->getProjectName();
            }
        }

        return $projectNames;
    }

    /**
     * {@inheritDoc}
     */
    public function getProjectNotAlreadyInstalledNames()
    {
        $projectNames = [];

        foreach ($this->getProjectConfigurationCollection() as $config) {
            if (false === $config->isInstalled()) {
                $projectNames[] = $config->getProjectName();
            }
        }

        return $projectNames;
    }

    protected function save($rows)
    {
        $data = ['projects' => []];
        foreach ($rows as $config) {
            $data['projects'][] = $config->getData();
        }

        file_put_contents(
            $this->filePath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @return Configuration[]
     */
    protected function getProjectConfigurationCollection()
    {
        static $configs;
        if (null === $configs) {
            $configs = [];

            foreach ($this->parse() as $data) {
                $config = $this->projectConfigurationFactory->create((array) $data);
                $projectName = $config->getProjectName();
                $configs[$projectName] = $config;
            }
        }

        return $configs;
    }

    /**
     * @return array
     */
    private function parse()
    {
        if (file_exists($this->filePath)) {
            $json = new Json();
            $data = $json->decode(file_get_contents($this->filePath));

            return (array) $data->projects;
        }

        return [];
    }

    /**
     * Gets the value of filePath.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }
}
