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

namespace Jarvis;

use Symfony\Component\DependencyInjection\Container;

class Jarvis
{
    /**
     * The service container.
     *
     * @var Container
     */
    private $container;

    /**
     * Constructor.
     *
     * @param Container $container The service container.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the service container.
     *
     * @return Container The service container.
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Runs the console application in the service container.
     *
     * @return integer The command exit status.
     */
    public function run()
    {
        return $this->container->get('console.application')->run(
            $this->container->get('console.input'),
            $this->container->get('console.output')
        );
    }
}
