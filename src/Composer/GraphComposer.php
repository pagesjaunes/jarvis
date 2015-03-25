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

use \JMS\Composer\Graph\DependencyGraph;
use Fhaculty\Graph\Graph;

class GraphComposer
{
    private $layoutVertex = array(
        'fillcolor' => '#eeeeee',
        'style' => 'filled, rounded',
        'shape' => 'box',
        'fontcolor' => '#314B5F',
    );

    private $layoutVertexRoot = array(
        'style' => 'filled, rounded, bold',
    );

    private $layoutEdge = array(
        'fontcolor' => '#FFEBFF',
        'fontsize' => 10,
        'color' => '#1A2833',
    );

    private $layoutEdgeDev = array(
        'style' => 'dashed',
    );

    private $dependencyGraph;

    private $format = 'svg';

    /**
     *
     * @param  DependencyGraph $dependencyGraph
     */
    public function __construct(DependencyGraph $dependencyGraph)
    {
        $this->dependencyGraph = $dependencyGraph;
    }

    /**
     *
     * @param  string                $dir
     * @return \Fhaculty\Graph\Graph
     */
    public function createGraph($vendorName = null, $excludeDevDependency = true)
    {
        $graph = new Graph();

        foreach ($this->dependencyGraph->getPackages() as $package) {
            $name = $package->getName();

            if (null !== $vendorName && false === strpos($name, $vendorName)) {
                continue;
            }

            $start = $graph->createVertex($name, true);

            $label = $name;
            if ($package->getVersion() !== null) {
                $label .= ': '.$package->getVersion();
            }

            $start->setLayout(array('label' => $label) + $this->getLayoutVertex($name));

            foreach ($package->getOutEdges() as $requires) {
                $targetName = $requires->getDestPackage()->getName();

                if (null !== $vendorName && false === strpos($targetName, $vendorName)) {
                    continue;
                }

                if ($excludeDevDependency && $requires->isDevDependency()) {
                    continue;
                }

                $target = $graph->createVertex($targetName, true);

                $label = $requires->getVersionConstraint();

                $edge = $start->createEdgeTo($target)->setLayout(array('label' => $label) + $this->layoutEdge);

                // if ($requires->isDevDependency()) {
                //     $edge->setLayout($this->layoutEdgeDev);
                // }
            }
        }

        // $graph->getVertex($this->dependencyGraph->getRootPackage()->getName())->setLayout($this->layoutVertexRoot);

        return $graph;
    }

    private function getLayoutVertex($packageName)
    {
        return $this->layoutVertex;
    }
}
