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

namespace Jarvis\Command\Core;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Ring\Client\CurlHandler;
use GuzzleHttp\Ring\Core;

class SelfUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates manifest.phar to the latest version')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $handler = new CurlHandler();
        $response = $handler([
            'http_method' => 'GET',
            'uri'         => '/jarvis/manifest.json',
            'headers'     => [
                'host'  => ['pagesjaunes.github.io'],
            ]
        ]);

        $response->wait();

        if ($response['status'] != 200) {
            throw new \RuntimeException(sprintf('%s: %s',
                $response['effective_url'],
                $response['reason']
            ));
        }

        $manager = new Manager(Manifest::load(Core::body($response)));
        $manager->update($this->getApplication()->getVersion(), true);
    }
}
