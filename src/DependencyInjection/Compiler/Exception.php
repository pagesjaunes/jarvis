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

/**
 * An exception thrown for container compiler related issues.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @codeCoverageIgnore
 */
class Exception extends \Exception
{
    /**
     * Creates a new exception for an abstract command definition.
     *
     * @param string $id The command service identifier.
     *
     * @return self The new exception.
     */
    public static function commandIsAbstract($id)
    {
        return new static(
            "The service \"$id\" is abstract and cannot be used as a command."
        );
    }

    /**
     * Creates a new exception for a service that is not a command.
     *
     * @param string $id The command service identifier.
     *
     * @return self The new exception.
     */
    public static function commandIsNotCommand($id)
    {
        return new static(
            sprintf(
                'The service "%s" is tagged as "%s" but is not a subclass of "%s".',
                $id,
                'console.command',
                'Symfony\Component\Console\Command\Command'
            )
        );
    }

    /**
     * Creates a new exception for a command definition that is not public.
     *
     * @param string $id The command service identifier.
     *
     * @return self The new exception.
     */
    public static function commandIsNotPublic($id)
    {
        return new static(
            "The service \"$id\" is not public and cannot be used as a command."
        );
    }

    /**
     * Creates a new exception for an abstract helper definition.
     *
     * @param string $id The helper service identifier.
     *
     * @return self The new exception.
     */
    public static function helperIsAbstract($id)
    {
        return new static(
            "The service \"$id\" is abstract and cannot be used as a helper."
        );
    }

    /**
     * Creates a new exception for a service that is not a helper.
     *
     * @param string $id The helper service identifier.
     *
     * @return self The new exception.
     */
    public static function helperIsNotHelper($id)
    {
        return new static(
            sprintf(
                'The service "%s" is tagged as "%s" but is not a subclass of "%s".',
                $id,
                'console.helper',
                'Symfony\Component\Console\Helper\Helper'
            )
        );
    }

    /**
     * Creates a new exception for a helper definition that is not public.
     *
     * @param string $id The helper service identifier.
     *
     * @return self The new exception.
     */
    public static function helperIsNotPublic($id)
    {
        return new static(
            "The service \"$id\" is not public and cannot be used as a helper."
        );
    }

    /**
     * Creates a new exception for an abstract event listener definition.
     *
     * @param string $id The listener service identifier.
     *
     * @return self The new exception.
     */
    public static function listenerIsAbstract($id)
    {
        return new static(
            "The service \"$id\" is abstract and cannot be used as an event listener."
        );
    }

    /**
     * Creates a new exception for a listener definition that is not public.
     *
     * @param string $id The listener service identifier.
     *
     * @return self The new exception.
     */
    public static function listenerIsNotPublic($id)
    {
        return new static(
            "The service \"$id\" is not public and cannot be used as an event listener."
        );
    }

    /**
     * Creates a new exception for an abstract event subscriber definition.
     *
     * @param string $id The subscriber service identifier.
     *
     * @return self The new exception.
     */
    public static function subscriberIsAbstract($id)
    {
        return new static(
            "The service \"$id\" is abstract and cannot be used as an event subscriber."
        );
    }

    /**
     * Creates a new exception for a subscriber definition that is not public.
     *
     * @param string $id The subscriber service identifier.
     *
     * @return self The new exception.
     */
    public static function subscriberIsNotPublic($id)
    {
        return new static(
            "The service \"$id\" is not public and cannot be used as an event subscriber."
        );
    }

    /**
     * Creates a new exception for a service that is not a subscriber.
     *
     * @param string $id The subscriber service identifier.
     *
     * @return self The new exception.
     */
    public static function subscriberIsNotSubscriber($id)
    {
        return new static(
            sprintf(
                'The service "%s" is tagged as "%s" but is not a subclass of "%s".',
                $id,
                'event.subscriber',
                'Symfony\Component\EventDispatcher\EventSubscriberInterface'
            )
        );
    }

    /**
     * Creates a new exception for a service that is missing a tag attribute.
     *
     * @param string $id        The service identifier.
     * @param string $tag       The name of the tag.
     * @param string $attribute The name of the attribute.
     *
     * @return self The new exception.
     */
    public static function tagAttributeNotSet($id, $tag, $attribute)
    {
        return new static(
            sprintf(
                'The service "%s" is missing the attribute "%s" for the tag "%s".',
                $id,
                $attribute,
                $tag
            )
        );
    }
}
