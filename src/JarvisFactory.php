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

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Jarvis\DependencyInjection\Compiler\CommandCompilerPass;
use Jarvis\DependencyInjection\Compiler\EventCompilerPass;
use Jarvis\DependencyInjection\Compiler\HelperCompilerPass;

class JarvisFactory
{
    const ApplicationClass = 'Jarvis\Console\Application';
    /**
     * Loads a container and returns it.
     *
     * If the cache file for the service container exists and is current, it
     * will be loaded and returned. Otherwise, a new container will be built
     * using the configuration file and the provided optional builder. The
     * builder will be used to make changes to the service container before
     * it is compiled and cached.
     *
     * It may be important to note that debug mode for the `ConfigCache` class
     * is enabled by default. This will ensure that cached configuration files
     * are updated whenever they are changed.
     *
     * @param string   $containerCacheFilePath     The container cache file path.
     * @param callable $containerBuilderCallable   The new container builder callable.
     * @param string   $compiledContainerClassName The compiled container class name.
     * @param boolean  $debug                      Is debugging mode enabled?
     *
     * @return Jarvis The loaded application.
     */
    public static function create(
        $containerCacheFilePath,
        callable $containerBuilderCallable = null,
        $compiledContainerClassName = 'AppCachedContainer',
        $debug = true
    ) {
        $cacheManager = new ConfigCache($containerCacheFilePath, $debug);

        if (!$cacheManager->isFresh()) {
            $container = static::createContainer();

            if (null !== $containerBuilderCallable) {
                $containerBuilderCallable($container);
            }

            if ($debug) {
                $filename = pathinfo($containerCacheFilePath, PATHINFO_DIRNAME).'/'.pathinfo($containerCacheFilePath, PATHINFO_FILENAME).'.xml';
                $container->setParameter('debug.container.dump', $filename);
            }

            $container->compile();

            $dumper = new PhpDumper($container);
            $cacheManager->write(
                $dumper->dump(array('class' => $compiledContainerClassName)),
                $container->getResources()
            );

            if ($debug) {
                $filename = $container->getParameter('debug.container.dump');
                $dumper = new XmlDumper($container);
                $filesystem = new Filesystem();
                $filesystem->dumpFile($filename, $dumper->dump(), null);
                try {
                    $filesystem->chmod($filename, 0666, umask());
                } catch (IOException $e) {
                    // discard chmod failure (some filesystem may not support it)
                }
            }
        }

        if (!class_exists($compiledContainerClassName)) {
            /** @noinspection PhpIncludeInspection */
            require $containerCacheFilePath;
        }

        return new Jarvis(new $compiledContainerClassName());
    }

    /**
     * Creates a service container with definitions.
     *
     * A new service container is created using a builder. The new container
     * will have a collection of services and compiler passes defined which
     * will be used by the application to run the console.
     *
     * @return ContainerBuilder The new service container.
     */
    public static function createContainer()
    {
        $container = new ContainerBuilder();

        static::registerApplication($container);
        static::registerCompilerPasses($container);
        static::registerEventDispatcher($container);
        static::registerHelperSet($container);
        static::registerInput($container);
        static::registerOutput($container);

        return $container;
    }

    /**
     * Returns the default compiler passes for the application.
     *
     * @return array The compiler passes.
     */
    protected static function getDefaultCompilerPasses()
    {
        return array(
            PassConfig::TYPE_BEFORE_OPTIMIZATION => array(
                new CommandCompilerPass(),
                new EventCompilerPass(),
                new HelperCompilerPass(),
            ),
        );
    }

    /**
     * Returns the default list of helpers for the application.
     *
     * Unfortunately there isn't a programmatic way of determining what the
     * current default list of helpers are. The helpers are hardcoded in the
     * app as instances of the helpers themselves. This means we have to
     * invoke the hardcoded method and practically work backwards in order
     * to attain the list of helpers.
     *
     * @return Helper[] The helpers.
     */
    protected static function getDefaultHelpers()
    {
        $class = new \ReflectionClass(static::ApplicationClass);

        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            $instance = $class->newInstanceWithoutConstructor();
        } else {
            $instance = unserialize(
                'O:'.strlen(static::ApplicationClass).':"'.static::ApplicationClass.'":0:{}'
            );
        }

        $method = $class->getMethod('getDefaultHelperSet');
        $method->setAccessible(true);

        /** @var Helper[] $helpers */
        $helpers = $method->invoke($instance);
        $default = array();

        foreach ($helpers as $helper) {
            $default['helper.'.$helper->getName()] = get_class($helper);
        }

        return $default;
    }

    /**
     * Registers the console application definition.
     *
     * @param ContainerBuilder $builder The service container builder.
     */
    protected static function registerApplication(ContainerBuilder $builder)
    {
        $definition = new Definition(static::ApplicationClass);
        // $definition->addArgument('%app.name%');
        // $definition->addArgument('%app.version%');

        // // default values
        // $builder->setParameter('app.name', 'UNKNOWN');
        // $builder->setParameter('app.version', 'UNKNOWN');

        $builder->setDefinition('console.application', $definition);
    }

    /**
     * Registers the default compiler passes with the container builder.
     *
     * @param ContainerBuilder $builder The service container builder.
     */
    protected static function registerCompilerPasses(ContainerBuilder $builder)
    {
        foreach (static::getDefaultCompilerPasses() as $type => $passes) {
            foreach ($passes as $pass) {
                $builder->addCompilerPass($pass, $type);
            }
        }
    }

    /**
     * Registers the event dispatcher definition.
     *
     * @param ContainerBuilder $builder The service container builder.
     */
    protected static function registerEventDispatcher(ContainerBuilder $builder)
    {
        $builder->setDefinition(
            'event_dispatcher',
            new Definition('Symfony\Component\EventDispatcher\EventDispatcher')
        );

        $builder->getDefinition('console.application')->addMethodCall(
            'setDispatcher',
            array(new Reference('event_dispatcher'))
        );
    }

    /**
     * Registers the helper set definition.
     *
     * @param ContainerBuilder $builder The service container builder.
     */
    protected static function registerHelperSet(ContainerBuilder $builder)
    {
        $definition = new Definition(
            'Symfony\Component\Console\Helper\HelperSet'
        );

        $builder->setDefinition('console.helper_set', $definition);
        $builder->getDefinition('console.application')->addMethodCall(
            'setHelperSet',
            array(new Reference('console.helper_set'))
        );

        foreach (static::getDefaultHelpers() as $id => $class) {
            $builder->setDefinition($id, new Definition($class));
            $definition->addMethodCall('set', array(new Reference($id)));
        }
    }

    /**
     * Registers the input definition.
     *
     * @param ContainerBuilder $builder The service container builder.
     */
    protected static function registerInput(ContainerBuilder $builder)
    {
        $builder->setDefinition(
            'console.input',
            new Definition('Symfony\Component\Console\Input\ArgvInput')
        );
    }

    /**
     * Registers the output definition.
     *
     * @param ContainerBuilder $builder The service container builder.
     */
    protected static function registerOutput(ContainerBuilder $builder)
    {
        $builder->setDefinition(
            'console.output',
            new Definition('Symfony\Component\Console\Output\ConsoleOutput')
        );
    }
}
