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
use Herrera\Phar\Update\Exception\InvalidArgumentException;
use Herrera\Phar\Update\Update;
use Herrera\Version\Comparator;
use Herrera\Version\Parser;
use Herrera\Version\Version;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Manages the Phar update process.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 * @author Tony Dubreil <tonydubreil@gmail.com>
 */
class Manager
{
    use \Psr\Log\LoggerAwareTrait;

    /**
     * The update manifest.
     *
     * @var Manifest
     */
    private $manifest;

    /**
     * The running file (the Phar that will be updated).
     *
     * @var string
     */
    private $runningFile;

    /**
     * The filesystem.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Sets the update manifest.
     *
     * @param Manifest $manifest The manifest.
     * @param Filesystem $filesystem The filesystem.
     */
    public function __construct(Manifest $manifest, Filesystem $filesystem)
    {
        $this->manifest = $manifest;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns the manifest.
     *
     * @return Manifest The manifest.
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * Returns the running file (the Phar that will be updated).
     *
     * @return string The file.
     */
    public function getRunningFile()
    {
        if (null === $this->runningFile) {
            $this->runningFile = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        }

        return $this->runningFile;
    }

    /**
     * Sets the running file (the Phar that will be updated).
     *
     * @param string $file The file name or path.
     *
     * @throws Exception\Exception
     * @throws InvalidArgumentException If the file path is invalid.
     */
    public function setRunningFile($file)
    {
        if (false === is_file($file)) {
            throw InvalidArgumentException::create(
                'The file "%s" is not a file or it does not exist.',
                $file
            );
        }

        $this->runningFile = $file;
    }

    /**
     * Updates the running Phar if any is available.
     *
     * @param string|Version $version  The current version.
     * @param boolean        $major    Lock to current major version?
     * @param boolean        $pre      Allow pre-releases?
     *
     * @return boolean TRUE if an update was performed, FALSE if none available.
     */
    public function update($version, $major = false, $pre = false, $newVersion = null)
    {
        if (false === ($version instanceof Version)) {
            $version = Parser::toVersion($version);
        }

        if ($newVersion !== null && false === ($newVersion instanceof Version)) {
            $newVersion = Parser::toVersion($newVersion);
        }

        if ($newVersion) {
            if (Comparator::isEqualTo($version, $newVersion)) {
                $this->logger->error(sprintf(
                    'You are already using jarvis version "%s".',
                    (string) $version
                ));
            }

            $update = $this->manifest->find($newVersion);

            if (null == $update) {
                $this->logger->error(sprintf(
                    'No update found for version "%s".',
                    (string) $newVersion
                ));

                return false;
            }
        } else {
            $update = $this->manifest->findRecent(
                $version,
                $major,
                $pre
            );
        }

        if (null == $update) {
            $this->logger->error(sprintf(
                'You are already using jarvis version "%s".',
                (string) $version
            ));

            return false;
        }

        if ($update instanceof Update) {
            if (!$this->downloadFile($update)) {
                return false;
            }
        }

        return true;
    }

    public function downloadFile(Update $update)
    {
        $targetFile = $this->getRunningFile();

        $this->logger->info(sprintf(
            'Updating file "%s" to version "%s".',
            (string) $update->getVersion(),
            $targetFile
        ));

        $url = $update->getUrl();

        $handler = new Ring\Client\CurlHandler();
        $response = $handler([
            'http_method' => 'GET',
            'uri'         => parse_url($url, PHP_URL_PATH),
            'headers'     => [
                'scheme' => [parse_url($url, PHP_URL_SCHEME)],
                'host'  => [parse_url($url, PHP_URL_HOST)],
            ],
            'client' => [
                'save_to' => $targetFile,
            ]
        ]);

        $response->wait();

        if (! $this->filesystem->exists($targetFile)) {
            $this->logger->error('The download of the new composer version failed for an unexpected reason');

            return false;
        }

        return true;
    }
}
