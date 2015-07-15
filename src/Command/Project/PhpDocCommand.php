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
use Symfony\Component\Filesystem\Filesystem;
use Jarvis\Project\ProjectConfiguration;

class PhpDocCommand extends BaseBuildCommand
{
    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generates the PHP API documentation for to one or all projects');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $remoteBuildDir = sprintf('%s/apidoc', $this->getRemoteBuildDir());
        $localBuildDir = sprintf('%s/apidoc', $this->getLocalBuildDir());

        $this->getSshExec()->run(
            strtr(
                'cd %project_dir% && sami.php update sami_config.php',
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            ),
            $output,
            OutputInterface::VERBOSITY_NORMAL
        );

        $this->getRemoteFilesystem()->mkdir($remoteBuildDir);
        $this->getLocalFilesystem()->mkdir($localBuildDir);
        $this->getRemoteFilesystem()->syncRemoteToLocal($remoteBuildDir, $localBuildDir, ['delete' => true]);

        $apiDocIndexFilepath = strtr(
            '%build_dir%/index.html',
            [
                '%project_name%' => $projectConfig->getProjectName(),
                '%build_dir%' => $localBuildDir
            ]
        );

        if (file_exists($apiDocIndexFilepath)) {
            $this->openFile($apiDocIndexFilepath);
        }
    }
}
