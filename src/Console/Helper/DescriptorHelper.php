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
 */

namespace Jarvis\Console\Helper;

use Symfony\Component\Console\Helper\DescriptorHelper as BaseDescriptorHelper;
use Jarvis\Console\Descriptor\JsonDescriptor;
use Jarvis\Console\Descriptor\MarkdownDescriptor;
use Jarvis\Console\Descriptor\TextDescriptor;
use Jarvis\Console\Descriptor\XmlDescriptor;

/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
class DescriptorHelper extends BaseDescriptorHelper
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this
            ->register('txt',  new TextDescriptor())
            ->register('xml',  new XmlDescriptor())
            ->register('json', new JsonDescriptor())
            ->register('md',   new MarkdownDescriptor())
        ;
    }
}
