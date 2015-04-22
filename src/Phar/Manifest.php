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

namespace Jarvis\Phar;

use GuzzleHttp\Ring;
use Herrera\Phar\Update\Manifest as BaseManifest;
use Herrera\Version\Version;

class Manifest extends BaseManifest
{
    /**
     * @param  string $url
     *
     * @return boolean
     *
     * @throws \RuntimeException
     */
    public static function download($url)
    {
        $handler = new Ring\Client\CurlHandler();
        $response = $handler([
            'http_method' => 'GET',
            'uri'         => parse_url($url, PHP_URL_PATH),
            'headers'     => [
                'scheme' => [parse_url($url, PHP_URL_SCHEME)],
                'host' => [parse_url($url, PHP_URL_HOST)],
            ],
        ]);

        $response->wait();

        if ($response['status'] != 200) {
            throw new \RuntimeException(sprintf('%s: %s',
                $response['effective_url'],
                $response['reason']
            ));
        }

        $json = Ring\Core::body($response);

        return self::load($json);
    }

    /**
     * @param  Version $version
     *
     * @return null|Update
     */
    public function find(Version $version)
    {
        $version = (string) $version;

        foreach ($this->getUpdates() as $update) {
            if ($version == (string) $update->getVersion()) {
                return $update;
            }
        }

        return;
    }
}
