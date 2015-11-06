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

class GitSummaryCommand  extends BaseGitCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Retrieve contributors to to one project or all projects, both large and small');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Git contributors for project "<info>%s</info>"</comment>',
                $projectName
            )
        );

        if (!is_dir($projectConfig->getLocalGitRepositoryDir())) {
            throw new \RuntimeException(sprintf('The directory "%s" does not exist', $projectConfig->getLocalGitRepositoryDir()));
        }

        $output->writeln('repo age: '.$this->getRepositoryAge($projectConfig->getLocalGitRepositoryDir()));
        $output->writeln('commits: '.$this->getCommitCount($projectConfig->getLocalGitRepositoryDir()));
        $output->writeln('authors:');
        $output->writeln($this->getListAuthors($projectConfig->getLocalGitRepositoryDir()));
        $output->writeln('');
    }

    /**
     * Fetch repository age from oldest commit.
     *
     * @param string $localGitRepositoryDir
     *
     * @return string
     */
    protected function getRepositoryAge($localGitRepositoryDir)
    {
        $result = $this->getExec()->exec(
            'git log --reverse --pretty=oneline --format="%ar" | head -n 1 | LC_ALL=C sed \'s/ago//\'',
            $localGitRepositoryDir
        );

        return $result[0];
    }

    /**
     * Get the commit total.
     *
     * @param string $localGitRepositoryDir
     *
     * @return string
     */
    protected function getCommitCount($localGitRepositoryDir)
    {
        $result = $this->getExec()->exec(
            'git log --oneline | wc -l | tr -d \' \'',
            $localGitRepositoryDir
        );

        return $result[0];
    }

    /**
     * @param string $localGitRepositoryDir
     *
     * @return array
     */
    protected function getListAuthors($localGitRepositoryDir)
    {
        return $this->getExec()->exec('git shortlog --numbered --summary', $localGitRepositoryDir);
    }
}
