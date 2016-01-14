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

class PhpDocCommand extends BaseBuildCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generates the PHP API documentation for to one or all projects');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->getSshExec()->passthru(
            strtr(
                'cd %project_dir% && /usr/local/bin/sami update sami_config.php'.($output->isDebug() ? ' -v' : ''),
                [
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            )
        );

        $localBuildDir = sprintf('%s/build/apidoc', $projectConfig->getLocalWebappDir());
        $apiDocIndexFilepath = strtr(
            '%build_dir%/index.html',
            [
                '%project_name%' => $projectConfig->getProjectName(),
                '%build_dir%' => $localBuildDir,
            ]
        );

        if (file_exists($apiDocIndexFilepath)) {
            $this->openFile($apiDocIndexFilepath);
        }
    }
}
