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

use GuzzleHttp\Ring;
use Herrera\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Phar\Manager;
use Jarvis\Phar\Manifest;

class SelfUpdateCommand extends Command
{
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    use \Psr\Log\LoggerAwareTrait;

    /**
     * @var string
     */
    private $pharUpdateManifestUrl;

    /**
     * Sets the value of pharUpdateManifestUrl.
     *
     * @param string $pharUpdateManifestUrl the phar update manifest url
     *
     * @return self
     */
    public function setPharUpdateManifestUrl($pharUpdateManifestUrl)
    {
        $this->pharUpdateManifestUrl = $pharUpdateManifestUrl;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates manifest.phar to the latest version')
            ->setDefinition([
                new InputArgument('version', InputArgument::OPTIONAL, 'The version to update to'),
                new InputOption('major', null, InputOption::VALUE_NONE, 'Lock to current major version?')
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return Version\Validator::isVersion($this->getApplication()->getVersion());
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runningFile = realpath($_SERVER['argv'][0]);

        $manifest = Manifest::download($this->pharUpdateManifestUrl);

        $manager = new Manager($manifest, $this->getLocalFilesystem());
        !$this->logger ?: $manager->setLogger($this->logger);

        $currentVersion = $this->getApplication()->getVersion();

        $newVersion = (null !== $input->getArgument('version')) ? $input->getArgument('version') : null;

        $major = $input->getOption('major'); // Lock to current major version?
        $pre = true; //Allow pre-releases?

        $manager->update($currentVersion, $major, $pre, $newVersion);
    }
}
