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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Jarvis\DependencyInjection\Compiler\Exception;

/**
 * Registers tagged event listeners and subscribers with the dispatcher.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class EventCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $builder)
    {
        $dispatcher = $builder->getDefinition('event_dispatcher');

        $this->registerListeners($builder, $dispatcher);
        $this->registerSubscribers($builder, $dispatcher);
    }

    /**
     * Registers the tagged event listeners.
     *
     * @param ContainerBuilder $builder    The service container builder.
     * @param Definition       $dispatcher The event dispatcher definition.
     *
     * @throws Exception If the listener could not be registered.
     */
    private function registerListeners(
        ContainerBuilder $builder,
        Definition $dispatcher
    ) {
        $ids = $builder->findTaggedServiceIds('event.listener');

        foreach ($ids as $id => $tags) {
            $listener = $builder->getDefinition($id);

            if ($listener->isAbstract()) {
                throw Exception::listenerIsAbstract($id);
            }

            if (!$listener->isPublic()) {
                throw Exception::listenerIsNotPublic($id);
            }

            foreach ($tags as $tag) {
                if (!isset($tag['event'])) {
                    throw Exception::tagAttributeNotSet(
                        $id,
                        'event.listener',
                        'event'
                    );
                }

                if (!isset($tag['method'])) {
                    throw Exception::tagAttributeNotSet(
                        $id,
                        'event.listener',
                        'method'
                    );
                }

                $dispatcher->addMethodCall(
                    'addListener',
                    array(
                        $tag['event'],
                        array(new Reference($id), $tag['method']),
                        isset($tag['priority']) ? $tag['priority'] : 0
                    )
                );
            }
        }
    }

    /**
     * Registers the tagged event subscribers.
     *
     * @param ContainerBuilder $builder    The service container builder.
     * @param Definition       $dispatcher The event dispatcher definition.
     *
     * @throws Exception If the subscriber could not be registered.
     */
    private function registerSubscribers(
        ContainerBuilder $builder,
        Definition $dispatcher
    ) {
        $ids = $builder->findTaggedServiceIds('event.subscriber');

        foreach ($ids as $id => $tags) {
            $subscriber = $builder->getDefinition($id);

            if ($subscriber->isAbstract()) {
                throw Exception::subscriberIsAbstract($id);
            }

            if (!$subscriber->isPublic()) {
                throw Exception::subscriberIsNotPublic($id);
            }

            $reflect = new ReflectionClass($subscriber->getClass());

            if (!$reflect->implementsInterface('Symfony\Component\EventDispatcher\EventSubscriberInterface')) {
                throw Exception::subscriberIsNotSubscriber($id);
            }

            $dispatcher->addMethodCall(
                'addSubscriber',
                array(new Reference($id))
            );
        }
    }
}
