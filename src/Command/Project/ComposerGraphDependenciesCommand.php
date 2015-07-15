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

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\GraphViz;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Composer\DependencyAnalyzer;
use Jarvis\Project\ProjectConfiguration;

class ComposerGraphDependenciesCommand extends BaseBuildCommand
{
    use \Jarvis\Ssh\SshExecAwareTrait;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var DependencyAnalyzer
     */
    private $dependencyAnalyzer;

    /**
     * @var string
     */
    private $graphComposerClass;

    /**
     * Either the name of full path to GraphViz layout.
     *
     * @var string
     */
    private $graphVizExecutable = 'dot';

    /**
     * @var string
     */
    private $format;

    /**
     * @var null|string
     */
    private $vendorName;

    /**
     * @var bool
     */
    private $composerInstallRequired;

    /**
     * Sets the Either the name of full path to GraphViz layout.
     *
     * @param string $graphVizExecutable the graphVizExecutable
     *
     * @return self
     */
    public function setGraphVizExecutable($graphVizExecutable)
    {
        $this->graphVizExecutable = $graphVizExecutable;

        return $this;
    }

    /**
     * Sets the value of cacheDir.
     *
     * @param mixed $cacheDir the cache dir
     *
     * @return self
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    protected function getCacheDir()
    {
        if (!$this->cacheDir) {
            $this->cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jarvis';
        }

        $this->getLocalFilesystem()->mkdir($this->cacheDir);

        return $this->cacheDir;
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
        $this->addOption('no-composer-install', null, InputOption::VALUE_NONE, 'Not execute composer install before generation graph');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->format = $input->getOption('format');
        $this->vendorName = $input->getOption('only-vendor-name');
        $this->composerInstallRequired = !$input->getOption('no-composer-install');
    }

    /**
     * @{inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        if ($this->composerInstallRequired) {
            $this->getApplication()->executeCommand('project:composer:install', [
                '--project-name' => $projectName
            ], $output);
        }

        $graphComposer = $this->getGraphComposer($projectConfig);
        $graph = $graphComposer->createGraph($this->vendorName);
        $localTargetFile = $this->saveGraphInFile($graph, $projectName);

        $output->writeln(
            sprintf(
                '<comment>Generates composer graph dependencies file <info>%s</info> for project "<info>%s</info>"</comment>',
                $localTargetFile,
                $projectConfig->getProjectName()
            )
        );

        if (file_exists($localTargetFile)) {
            $this->openFile($localTargetFile);
        }
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

        if (null === $this->dependencyAnalyzer) {
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
        return null === $this->graphComposerClass ? 'Jarvis\Composer\GraphComposer' : $this->graphComposerClass;
    }

    protected function saveGraphInFile(Graph $graph, $projectName)
    {
        $localTargetFile = $this->getLocalTargetFile($projectName);
        $remoteTargetFile = $this->getRemoteTargetFile($projectName);

        $graphviz = new GraphViz($graph);
        $graphviz->setFormat($this->format);

        $tmpDir = sprintf('%s/jarvis/composer_graph_dependencies', $this->getCacheDir());

        $tmpFile = tempnam($tmpDir, 'composer_graph_dependencies');
        if ($tmpFile === false) {
            throw new UnexpectedValueException('Unable to get temporary file name for graphviz script');
        }

        $ret = file_put_contents($tmpFile, $graphviz->createScript(), LOCK_EX);
        if ($ret === false) {
            throw new UnexpectedValueException(sprintf(
                'Unable to write graphviz script to temporary file in %s',
                $tmpDir
            ));
        }

        $remoteGraphvizScriptFile = sprintf(
            '%s/%s.%s',
            pathinfo($remoteTargetFile, PATHINFO_DIRNAME),
            pathinfo($remoteTargetFile, PATHINFO_FILENAME),
            'dot'
        );

        $this->getRemoteFilesystem()->copyLocalFileToRemote($tmpFile, $remoteGraphvizScriptFile);

        $commandLine = sprintf(
            '%s -T %s %s -o %s',
            $this->graphVizExecutable,
            $this->format,
            $remoteGraphvizScriptFile,
            $remoteTargetFile
        );
        $this->getSshExec()->exec($commandLine);

        $this->getRemoteFilesystem()->copyRemoteFileToLocal($remoteTargetFile, $localTargetFile);

        return $localTargetFile;
    }

    /**
     * @param  string $projectName
     *
     * @return string
     */
    protected function getLocalTargetFile($projectName)
    {
        $localBuildDir = sprintf('%s/graph_dependencies', $this->getLocalBuildDir());
        $this->getLocalFilesystem()->mkdir($localBuildDir);
        return sprintf(
            '%s/%s.%s',
            $localBuildDir,
            $projectName,
            $this->format
        );
    }

    /**
     * @param  string $projectName
     *
     * @return string
     */
    protected function getRemoteTargetFile($projectName)
    {
        $remoteBuildDir = sprintf('%s/graph_dependencies', $this->getRemoteBuildDir());
        $this->getRemoteFilesystem()->mkdir($remoteBuildDir);
        return sprintf(
            '%s/%s.%s',
            $remoteBuildDir,
            $projectName,
            $this->format
        );
    }
}
