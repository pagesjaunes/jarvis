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

class ComposerCommand extends BaseCommand
{
    use \Jarvis\Ssh\SshExecAwareTrait;

    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    private $commandOptions;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Install dependencies with composer and build assets.');

        $this->addOption('optimize-autoloader', 'o', InputOption::VALUE_NONE, 'Optimize autoloader during autoloader dump');

        $this->addOption('prefer-dist', null, InputOption::VALUE_NONE, 'Forces installation from package dist even for dev versions.');

        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs the operations but will not execute anything (implicitly enables --verbose).');

        $this->addOption('no-dev', null, InputOption::VALUE_NONE, 'Disables installation of require-dev packages.');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->commandOptions = [];
        if ($input->getOption('optimize-autoloader')) {
            $this->commandOptions['optimize-autoloader'] = '--optimize-autoloader';
        }
        if ($input->getOption('dry-run')) {
            $this->commandOptions['dry-run'] = '--dry-run';
        }
        if ($input->getOption('no-dev')) {
            $this->commandOptions['no-dev'] = '--no-dev';
        }
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $commandName = str_replace('project:composer:', null, $this->getName());

        $returnStatusCode = $this->createRemoteVendorDir($projectConfig, $output);

        if (0 === $returnStatusCode) {
            $returnStatusCode = $this->executeComposerCommandOnRemoteServer(
                $commandName,
                $projectConfig,
                $output
            );
        }

        if (0 === $returnStatusCode) {
            switch ($commandName) {
                case 'install':
                case 'update':
                    $returnStatusCode = $this->synchronizeRemoteProjectVendorToLocal($projectConfig, $output);
                    break;
            }
        }

        return $returnStatusCode;
    }

    /**
     * @param ProjectConfiguration $projectConfig
     * @param OutputInterface      $output
     *
     * @return int Exit code
     */
    protected function createRemoteVendorDir(ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        if ($output->isVeryVerbose()) {
            $output->writeln(
                sprintf(
                    '<comment>Creates composer autoload file for use vendor dir "<info>%s</info>" for project "<info>%s</info>"</comment>',
                    $projectConfig->getRemoteVendorDir(),
                    $projectConfig->getProjectName()
                )
            );
        }

        $filesystem = $this->getLocalFilesystem();
        $filesystem->remove($projectConfig->getLocalWebappDir().'/vendor');
        $autoloadFilePath = $projectConfig->getLocalWebappDir().'/vendor/autoload.php';
        $autoloadContent = sprintf(
            '<?php return require \'%s/autoload.php\';',
            $projectConfig->getRemoteVendorDir()
        );
        $filesystem->dumpFile($autoloadFilePath, $autoloadContent);

        if ($output->isVeryVerbose()) {
            $output->writeln(
                sprintf(
                    '<comment>Creates remote vendor dir for project "<info>%s</info>"</comment>',
                    $projectConfig->getProjectName()
                )
            );
        }

        $this->getSshExec()->exec(
            strtr(
                'test -d %composer_vendor_dir% || mkdir -p %composer_vendor_dir%',
                [
                    '%composer_vendor_dir%' => $projectConfig->getRemoteVendorDir(),
                ]
            )
        );

        if (0 !== $this->getSshExec()->getLastReturnStatus()) {
            $this->getSshExec()->checkStatus($output);
        }

        return $this->getSshExec()->getLastReturnStatus();
    }

    /**
     * @param string               $commandName
     * @param ProjectConfiguration $projectConfig
     * @param OutputInterface      $output
     *
     * @return int Exit code
     */
    protected function executeComposerCommandOnRemoteServer($commandName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln('<comment>'.$this->getDescription().'</comment>');

        $this->getSshExec()->passthru(
            strtr(
                'COMPOSER_VENDOR_DIR=%COMPOSER_VENDOR_DIR% composer %command_name% %command_options% --working-dir=%project_dir% '.($output->isDebug() ? ' -vvv' : ''),
                [
                    '%COMPOSER_VENDOR_DIR%' => $projectConfig->getRemoteVendorDir(),
                    '%command_name%' => $commandName,
                    '%command_options%' => is_array($this->commandOptions) ? implode(' ', $this->commandOptions) : null,
                    '%project_dir%' => $projectConfig->getRemoteWebappDir(),
                ]
            )
        );

        return $this->getSshExec()->getLastReturnStatus();
    }

    /**
     * @param ProjectConfiguration $projectConfig
     * @param OutputInterface      $output
     *
     * @return int Exit code
     */
    protected function synchronizeRemoteProjectVendorToLocal(ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                '<comment>Synchronize remote project vendor to local for the project "<info>%s</info>"</comment>',
                $projectConfig->getProjectName()
            )
        );

        $this->getLocalFilesystem()->mkdir($projectConfig->getLocalVendorDir());

        return $this->getRemoteFilesystem()->syncRemoteToLocal(
            $projectConfig->getRemoteVendorDir(),
            $projectConfig->getLocalVendorDir(),
            ['delete' => true]
        );
    }
}
