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

use Jarvis\Process\ExecAwareTrait;
use Jarvis\Project\ProjectConfiguration;
use Symfony\Component\Console\Output\OutputInterface;

class BaseGitCommand extends BaseCommand
{
    use ExecAwareTrait;

    protected $gitCommand;

    protected $colorsConsoleOutput = false;

    /**
     * {@inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Git %s for project "<info>%s</info>"</comment>',
                $this->getName(),
                $projectName
            )
        );

        if (!is_dir($projectConfig->getLocalGitRepositoryDir())) {
            throw new \RuntimeException(sprintf('The directory "%s" does not exist', $projectConfig->getLocalGitRepositoryDir()));
        }

        if (empty($this->gitCommand)) {
            throw new \LogicException('Git command is empty');
        }

        if ($this->colorsConsoleOutput) {
            ob_start();
        }

        $this->getExec()->passthru(
            sprintf('git %s', $this->gitCommand),
            $projectConfig->getLocalGitRepositoryDir()
        );

        if ($this->colorsConsoleOutput) {
            $output->writeln(
                strtr(
                    ob_get_clean(),
                    [
                        'up-to-date' => '<info>up-to-date</info>',
                        'is behind' => '<info>is behind</info>',
                        'can be fast-forwarded' => '<info>can be fast-forwarded</info>',
                        'modified:' => '<info>modified:</info>',
                        'both modified' => '<error>both modified:</error>',
                        'deleted:' => '<error>deleted:</error>', // red color
                        'deleted by us' => '<error>deleted by us</error>', // red color
                        '-  ' => '<error>- </error>', // red color
                        '+  ' => '<info>+</info>', // green color
                    ]
                )
            );
        }

        return $this->getExec()->getLastReturnStatus();
    }

    /**
     * Sets the value of gitCommand.
     *
     * @param string $gitCommand the git command
     *
     * @return self
     */
    public function setGitCommand($gitCommand)
    {
        $this->gitCommand = $gitCommand;

        return $this;
    }

    public function enableColorsConsoleOutput()
    {
        $this->colorsConsoleOutput = true;
    }
}
