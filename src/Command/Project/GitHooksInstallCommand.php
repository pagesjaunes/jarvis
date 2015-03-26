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

class GitHooksInstallCommand extends BaseCommand
{
    use \Jarvis\Process\ExecAwareTrait;
    use \Jarvis\Process\CommandExistTrait;
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    private $skeletonGitHooksDir;
    private $workingDir;

    /**
     * Sets the value of workingDir.
     *
     * @param mixed $workingDir the working dir
     *
     * @return self
     */
    public function setWorkingDir($workingDir)
    {
        $this->workingDir = $workingDir;

        return $this;
    }

    /**
     * Sets the value of skeletonGitHooksDir.
     *
     * @param mixed $skeletonGitHooksDir the skeleton git hooks dir
     *
     * @return self
     */
    public function setSkeletonGitHooksDir($skeletonGitHooksDir)
    {
        $this->skeletonGitHooksDir = $skeletonGitHooksDir;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Install git hooks in to one or all projects.');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->runningFile = realpath($_SERVER['argv'][0]);
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->installGitHook('pre-commit', $projectConfig, $output);
        $this->installGitHook('post-merge', $projectConfig, $output);
    }

    protected function installGitHook($gitHookName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $content = $this->renderTemplate($gitHookName, $projectConfig->getProjectName());
        $filename = $this->getTargetGitHookFile($gitHookName, $projectConfig);

        $this->getLocalFilesystem()->dumpFile($filename, $content, $mode = 0666);
        $this->getExec()->exec('chmod +x '.$filename);

        $output->writeln(sprintf(
            '<comment>Install git hook <info>%s</info> for project "<info>%s</info>"</comment>',
            $gitHookName,
            $projectConfig->getProjectName()
        ));
    }

    protected function getTargetGitHookFile($gitHookName, ProjectConfiguration $projectConfig)
    {
        return sprintf('%s/%s', $projectConfig->getLocalGitHooksDir(), $gitHookName);
    }

    protected function renderTemplate($templateName, $projectName)
    {
        $filepath = $this->skeletonGitHooksDir.'/'.$templateName;

        if (!file_exists($filepath)) {
            throw new \RuntimeException(sprintf('The git hook template %s does not exist', $filepath));
        }

        return strtr(
            file_get_contents($filepath),
            [
                '{{jarvis_command_name}}' => sprintf(
                    '%s --working-dir=%s',
                    $this->runningFile,
                    $this->workingDir
                ),
                '{{project_name}}' => $projectName,
            ]
        );
    }
}
