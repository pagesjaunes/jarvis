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

namespace Jarvis\Command\Project;

use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;

class SymfonyAssetsBuildCommand extends BaseSymfonyCommand
{
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    /**
     * @var string
     */
    protected $localCdnRootDir;

    /**
     * @var string
     */
    protected $remoteCdnRootDir;

    /**
     * Sets the value of localCdnRootDir.
     *
     * @param string $localCdnRootDir the remote cdn root dir
     *
     * @return self
     */
    public function setLocalCdnRootDir($localCdnRootDir)
    {
        $this->localCdnRootDir = $localCdnRootDir;

        return $this;
    }

    /**
     * Sets the value of remoteCdnRootDir.
     *
     * @param string $remoteCdnRootDir the remote cdn root dir
     *
     * @return self
     */
    public function setRemoteCdnRootDir($remoteCdnRootDir)
    {
        $this->remoteCdnRootDir = $remoteCdnRootDir;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Builds assets for to one or all projects');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Installing and building assets for project "<info>%s</info>"</comment>',
                $projectConfig->getProjectName()
            )
        );

        $this->getSymfonyRemoteConsoleExec()->run(
            $projectConfig->getRemoteSymfonyConsolePath(),
            strtr('assets:install %cdn_dir%', ['%cdn_dir%' => $this->remoteCdnRootDir]),
            $this->getSymfonyEnv(),
            $output
        );

        return $this->getSymfonyRemoteConsoleExec()->getLastReturnStatus();
    }
}
