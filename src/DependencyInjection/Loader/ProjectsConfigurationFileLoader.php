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

namespace DependencyInjection\Loader;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\FileLoader;

class ProjectsConfigurationFileLoader extends FileLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);

        $this->container->addResource(new FileResource($path));

        $result = parse_ini_file($path, true);
        // $result = parse_ini_file($path, true);
        // if (false === $result || array() === $result) {
        //     throw new InvalidArgumentException(sprintf('The "%s" file is not valid.', $resource));
        // }

        // if (isset($result['parameters']) && is_array($result['parameters'])) {
        //     foreach ($result['parameters'] as $key => $value) {
        //         $this->container->setParameter($key, $value);
        //     }
        // }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'ini' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
