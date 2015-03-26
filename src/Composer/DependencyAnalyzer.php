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

namespace Jarvis\Composer;

use Herrera\Json\Json;
use JMS\Composer\Graph\DependencyGraph;
use JMS\Composer\Graph\PackageNode;

/**
 * Analyzes dependencies of a project, and returns them as a graph.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Tony Dubreil <tony.dubreil@niji.fr>
 */
class DependencyAnalyzer
{
    /**
     * @param string $composerJsonFileContent
     * @param string $composerLockFileContent
     * @param string $installedFileDevContent
     * @param string $installedFileContent
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return \JMS\Composer\Graph\DependencyGraph
     */
    public function analyze($composerJsonFileContent, $composerLockFileContent, $installedFileContent, $installedFileDevContent = null, $connectRequireDev = false)
    {
        $json = new Json();

        $rootPackageData = $json->decode($composerJsonFileContent, true);

        if (! isset($rootPackageData['name'])) {
            $rootPackageData['name'] = '__root';
        }

        // If there is no composer.lock file, then either the project has no
        // dependencies, or the dependencies were not installed.
        if (empty($composerLockFileContent)) {
            if ($this->hasDependencies($rootPackageData)) {
                throw new \RuntimeException(sprintf('You need to run "composer install" in "%s" before analyzing dependencies.', $dir));
            }

            $graph = new DependencyGraph(new PackageNode($rootPackageData['name'], $rootPackageData));

            // Connect built-in dependencies for example on the PHP version, or
            // on PHP extensions. For these, composer does not create a composer.lock.
            if (isset($rootPackageData['require'])) {
                foreach ($rootPackageData['require'] as $name => $versionConstraint) {
                    $this->connect($graph, $rootPackageData['name'], $name, $versionConstraint);
                }
            }

            if ($connectRequireDev && isset($rootPackageData['require-dev'])) {
                foreach ($rootPackageData['require-dev'] as $name => $versionConstraint) {
                    $this->connect($graph, $rootPackageData['name'], $name, $versionConstraint);
                }
            }

            return $graph;
        }

        $graph = new DependencyGraph(new PackageNode($rootPackageData['name'], $rootPackageData));

        // Add regular packages.
        if (!empty($installedFileContent)) {
            foreach ($json->decode($installedFileContent, true) as $packageData) {
                $graph->createPackage($packageData['name'], $packageData);
                $this->processLockedData($graph, $packageData);
            }
        }

        // Add development packages.
        if ($connectRequireDev && !empty($installedFileDevContent)) {
            foreach ($json->decode($installedFileDevContent, true) as $packageData) {
                $graph->createPackage($packageData['name'], $packageData);
                $this->processLockedData($graph, $packageData);
            }
        }

        // Connect dependent packages.
        foreach ($graph->getPackages() as $packageNode) {
            $packageData = $packageNode->getData();

            if (isset($packageData['require'])) {
                foreach ($packageData['require'] as $name => $version) {
                    $this->connect($graph, $packageData['name'], $name, $version);
                }
            }

            if ($connectRequireDev && isset($packageData['require-dev'])) {
                foreach ($packageData['require-dev'] as $name => $version) {
                    $this->connect($graph, $packageData['name'], $name, $version);
                }
            }
        }

        // Populate graph with versions, and source references.
        $lockData = $json->decode($composerLockFileContent, true);
        if (isset($lockData['packages'])) {
            foreach ($lockData['packages'] as $lockedPackageData) {
                $this->processLockedData($graph, $lockedPackageData);
            }
        }
        if ($connectRequireDev && isset($lockData['packages-dev'])) {
            foreach ($lockData['packages-dev'] as $lockedPackageData) {
                $this->processLockedData($graph, $lockedPackageData);
            }
        }

        return $graph;
    }

    private function connect(DependencyGraph $graph, $sourceName, $destName, $version)
    {
        if ('php' === $destName) {
            return;
        }

        // If the dest package is available, just connect it.
        if ($graph->hasPackage($destName)) {
            $graph->connect($sourceName, $destName, $version);

            return;
        }

        // If the dest package is not available, let's check to see if there is
        // some aggregate package that replaces our dest package, and connect to
        // this package.
        if (null !== $aggregatePackage = $graph->getAggregatePackageContaining($destName)) {
            $graph->connect($sourceName, $aggregatePackage->getName(), $version);

            return;
        }
    }

    private function processLockedData(DependencyGraph $graph, array $lockedPackageData)
    {
        $packageName = null;
        if (isset($lockedPackageData['name'])) {
            $packageName = $lockedPackageData['name'];
        } elseif (isset($lockedPackageData['package'])) {
            $packageName = $lockedPackageData['package'];
        }

        if (null === $packageName) {
            return;
        }

        $package = $graph->getPackage($packageName);
        if (null === $package) {
            return;
        }

        $package->setVersion($lockedPackageData['version']);

        if (isset($lockedPackageData['installation-source'])
                && isset($lockedPackageData[$lockedPackageData['installation-source']]['reference'])
                && $lockedPackageData['version'] !== $lockedPackageData[$lockedPackageData['installation-source']]['reference']) {
            $package->setSourceReference($lockedPackageData[$lockedPackageData['installation-source']]['reference']);
        }
    }

    /**
     * @param array   $config
     * @param boolean $connectRequireDev
     *
     * @return bool
     */
    private function hasDependencies(array $config, $connectRequireDev = false)
    {
        if (isset($config['require']) && $this->hasUserlandDependency($config['require'])) {
            return true;
        }

        if ($connectRequireDev && isset($config['require-dev']) && $this->hasUserlandDependency($config['require-dev'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array $requires
     *
     * @return bool
     */
    private function hasUserlandDependency(array $requires)
    {
        if (empty($requires)) {
            return false;
        }

        foreach ($requires as $name => $versionConstraint) {
            if ('php' === $name) {
                continue;
            }

            if (0 === strpos($name, 'ext-')) {
                continue;
            }

            return true;
        }

        return false;
    }
}
