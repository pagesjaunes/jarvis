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
 * Registers tagged helper services with the console.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class HelperCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $builder)
    {
        $ids = $builder->findTaggedServiceIds('console.helper');
        $helperSet = $builder->getDefinition('console.helper_set');

        foreach ($ids as $id => $tags) {
            $helper = $builder->getDefinition($id);

            if ($helper->isAbstract()) {
                throw Exception::helperIsAbstract($id);
            }

            if (!$helper->isPublic()) {
                throw Exception::helperIsNotPublic($id);
            }

            $reflect = new ReflectionClass($helper->getClass());

            if (!$reflect->isSubclassOf('Symfony\Component\Console\Helper\Helper')) {
                throw Exception::helperIsNotHelper($id);
            }

            foreach ($tags as $tag) {
                $helperSet->addMethodCall(
                    'set',
                    array(
                        new Reference($id),
                        isset($tag['alias']) ? $tag['alias'] : null
                    )
                );
            }
        }
    }
}
