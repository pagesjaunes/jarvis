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

use Fhaculty\Graph\GraphViz;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Composer\DependencyAnalyzer;
use Jarvis\Composer\GraphComposer;
use Jarvis\Project\ProjectConfiguration;

class ComposerGraphDependenciesCommand extends BaseCommand
{
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    /**
     * @var DependencyAnalyzer
     */
    private $dependencyAnalyzer;

    /**
     * @var string
     */
    private $graphComposerClass;

    /**
     * @var string
     */
    private $localBuildDir;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var null|string
     */
    protected $vendorName;

    /**
     * Sets the value of localBuildDir.
     *
     * @param string $localBuildDir the local build dir
     *
     * @return self
     */
    public function setLocalBuildDir($localBuildDir)
    {
        $this->localBuildDir = $localBuildDir;

        return $this;
    }

    /**
     * Sets the value of dependencyAnalyzer.
     *
     * @param DependencyAnalyzer $dependencyAnalyzer the dependency analyzer
     *
     * @return self
     */
    public function setDependencyAnalyzer(DependencyAnalyzer $dependencyAnalyzer)
    {
        $this->dependencyAnalyzer = $dependencyAnalyzer;

        return $this;
    }

    /**
     * Sets the value of graphComposerClass.
     *
     * @param string $graphComposerClass the graph composer class
     *
     * @return self
     */
    public function setGraphComposerClass($graphComposerClass)
    {
        $this->graphComposerClass = $graphComposerClass;

        return $this;
    }

    protected function configure()
    {
        $this->setDescription('Creates a dependency graph for the given project');

        $this->addOption('only-vendor-name', null, InputOption::VALUE_REQUIRED, 'Only display graph for package with this vendor name');
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'File format (pdf, svg, png, jpeg)', 'pdf');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->format = $input->getOption('format');
        $this->vendorName = $input->getOption('only-vendor-name');

        $this->buildDir = sprintf(
            '%s/graph_dependencies',
            $this->localBuildDir
        );

        $this->getLocalFilesystem()->mkdir($this->buildDir);
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $this->getApplication()->executeCommand('project:composer:install', [
            '--project-name' => $projectName
        ], $output);

        $targetFile = $this->getTargetFilePath($projectName);

        $output->writeln(
            sprintf(
                '<comment>Generates composer graph dependencies file <info>%s</info> for project "<info>%s</info>"</comment>',
                $targetFile,
                $projectConfig->getProjectName()
            )
        );

        $graphComposer = $this->getGraphComposer($projectConfig);

        $graph = $graphComposer->createGraph($this->vendorName);

        $graphviz = new GraphViz($graph);
        $graphviz->setFormat($this->format);
        $this->saveGraphInFile($graphviz, $targetFile);
    }

    protected function getGraphComposer(ProjectConfiguration $projectConfig)
    {
        $composerJsonFileContent = $this->getRemoteFilesystem()->getRemoteFileContent(
            $projectConfig->getRemoteWebappDir().'/composer.json'
        );

        $composerLockFileContent = $this->getRemoteFilesystem()->getRemoteFileContent(
            $projectConfig->getRemoteWebappDir().'/composer.lock'
        );

        $installedFileContent = $this->getRemoteFilesystem()->getRemoteFileContent(
            $projectConfig->getRemoteVendorDir().'/composer/installed.json'
        );

        if (null == $this->dependencyAnalyzer) {
            $this->dependencyAnalyzer = new DependencyAnalyzer();
        }

        $dependencyGraph = $this->dependencyAnalyzer->analyze(
            $composerJsonFileContent,
            $composerLockFileContent,
            $installedFileContent
        );

        $class = $this->getGraphComposerClass();
        return new $class($dependencyGraph);
    }

    protected function getGraphComposerClass()
    {
        return null == $this->graphComposerClass ? 'Jarvis\Composer\GraphComposer' : $this->graphComposerClass;
    }

    protected function saveGraphInFile(GraphViz $graphviz, $targetFile)
    {
        $this->getLocalFilesystem()->rename(
            $graphviz->createImageFile(),
            $targetFile,
            true // overwrite
        );
    }

    protected function getTargetFilePath($projectName)
    {
        return sprintf(
            '%s/%s.%s',
            $this->buildDir,
            $projectName,
            $this->format
        );
    }
}
