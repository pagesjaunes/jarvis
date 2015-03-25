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

namespace Jarvis\DependencyInjection\Compiler;

use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Jarvis\DependencyInjection\Compiler\Exception;

/**
 * Registers tagged command services with the console.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class CommandCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $builder)
    {
        $ids = $builder->findTaggedServiceIds('console.command');
        $console = $builder->getDefinition('console.application');

        foreach ($ids as $id => $tags) {
            $command = $builder->getDefinition($id);

            if ($command->isAbstract()) {
                throw Exception::commandIsAbstract($id);
            }

            if (!$command->isPublic()) {
                throw Exception::commandIsNotPublic($id);
            }

            $reflect = new ReflectionClass($command->getClass());

            if (!$reflect->isSubclassOf('Symfony\Component\Console\Command\Command')) {
                throw Exception::commandIsNotCommand($id);
            }

            $console->addMethodCall('add', array(new Reference($id)));
        }
    }
}
