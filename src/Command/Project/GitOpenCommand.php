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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Project\ProjectConfiguration;

class GitOpenCommand extends BaseGitCommand
{
    /**
     * @var string
     */
    private $remote;

    /**
     * @var string
     */
    private $branchName;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Opens the website for a repository in your browser');

        $this->addOption('remote', null, InputOption::VALUE_REQUIRED, 'Remote', 'origin');
        $this->addOption('branch', null, InputOption::VALUE_REQUIRED, 'Branch name');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->remote = $input->getOption('remote');
        $this->branchName = $input->getOption('branch');
    }


    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Opens the website for a repository in your browser for the project "<info>%s</info>"</comment>',
                $projectName
            )
        );

        if (!is_dir($projectConfig->getLocalGitRepositoryDir())) {
            throw new \RuntimeException(sprintf('The directory "%s" does not exist', $projectConfig->getLocalGitRepositoryDir()));
        }

        $giturl = $this->getGiturl($projectConfig);

        $this->openUrl($giturl);

        return 0;
    }

    protected function getGiturl(ProjectConfiguration $projectConfig)
    {
        $giturl = $this->getExec()->exec(
            'git config --get remote.'.$this->remote.'.url',
            $projectConfig->getLocalGitRepositoryDir()
        );

        if ($this->getExec()->getLastReturnStatus() !== 0) {
            throw new \LogicException('Error retrieve git URL');
        }

        if (0 === strpos($giturl, 'git@')) {
            $giturl = str_replace(':', '/', $giturl);
            $giturl = str_replace('git@', 'http://', $giturl);
        }

        if (false !== strpos($giturl, '.git')) {
            $giturl = str_replace('.git', '', $giturl);
        }

        $branchName = null === $this->branchName ?
            $this->getCurrentBranch($projectConfig)
            :
            $this->branchName;

        $giturl .= '/tree/'.$branchName;

        return $giturl;
    }

    protected function getCurrentBranch($projectConfig)
    {
        $giturl = $this->getExec()->exec(
            'git symbolic-ref -q --short HEAD',
            $projectConfig->getLocalGitRepositoryDir()
        );

        if ($this->getExec()->getLastReturnStatus() !== 0) {
            throw new \LogicException('Error retrieve branch name');
        }

        return $giturl;
    }

    protected function openUrl($url)
    {
        $this->getExec()->exec(strtr(
            'which xdg-open && xdg-open %url% || which open && open %url%',
            [
                '%url%' => $url
            ]
        ));
    }
}
