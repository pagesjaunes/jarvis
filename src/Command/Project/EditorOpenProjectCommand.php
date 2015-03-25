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

class EditorOpenProjectCommand extends BaseCommand
{
    use \Psr\Log\LoggerAwareTrait;
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    /**
     * @var string
     */
    private $configDir;

    /**
     * @var array
     */
    private $directoriesConfig = [];

    /**
     * Sets the value of configDir.
     *
     * @param string $configDir the config dir
     *
     * @return self
     */
    public function setConfigDir($configDir)
    {
        $this->configDir = $configDir;

        return $this;
    }

    public function addDirectoriesConfig(array $directoriesConfig)
    {
        foreach ($directoriesConfig as $config) {
            $this->directoriesConfig[] = [
                'name' => $config['name'],
                'path' => realpath($config['path']),
                'follow_symlinks' => $config['follow_symlinks']
            ];
        }
    }

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Open project in editor');

        $this->addOption('editor', null, InputOption::VALUE_REQUIRED, 'Which editor to use', 'subl');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Rewrites project config file');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->editor = $input->getOption('editor');
        $this->rewritesConfigFile = $input->getOption('force');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        switch ($this->editor) {
            case 'subl':
                $this->openProjectWithSublimeText($projectConfig, $output);
                break;
            default:
                throw new \RuntimeException(sprintf('This editor "%s" is not supported yet', $this->editor));
        }
    }

    protected function openProjectWithSublimeText(ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $projectConfigPath = sprintf(
            '%s/sublimetext/%s.sublime-project',
            $this->configDir,
            $projectConfig->getProjectName()
        );

        !$this->logger ?: $this->logger->debug(sprintf(
            'Checks the existence of file %s',
            $projectConfigPath
        ));

        if (! $this->getLocalFilesystem()->exists($projectConfigPath) || $this->rewritesConfigFile) {
            $configData = [
                'folders' => [
                    [
                        'follow_symlinks' => false,
                        'name' => $projectConfig->getProjectName(),
                        'path' => realpath($projectConfig->getLocalGitRepositoryDir())
                    ],
                    [
                        'follow_symlinks' => false,
                        'name' => 'vendor',
                        'path' => realpath($projectConfig->getLocalVendorDir())
                    ]
                ]
            ];

            if ($projectConfig->getLocalAssetsDir()) {
                $configData['folders'][] = [
                    'follow_symlinks' => false,
                    'name' => 'assets_project',
                    'path' => realpath($projectConfig->getLocalAssetsDir())
                ];
            }

            foreach ($this->directoriesConfig as $configCommonDirectory) {
                $configData['folders'][] = $configCommonDirectory;
            }

            $content = json_encode($configData, JSON_PRETTY_PRINT);

            !$this->logger ?: $this->logger->debug(sprintf(
                'Dumps project json config into a file "<info>%s</info>".',
                $projectConfigPath
            ));

            $this->getLocalFilesystem()->dumpFile($projectConfigPath, $content);
        }

        if ('' !== exec('which subl')) {
            $commandLine = 'subl -n ' . $projectConfigPath . (defined('PHP_WINDOWS_VERSION_BUILD') ? '' : ' > `tty`');
            !$this->logger ?: $this->logger->debug(sprintf('Executes command line %s', $commandLine));
            system($commandLine);
        } else {
            $output->writeln('<error><info>subl</info> command not found</error>');
        }
    }
}
