<?php

namespace Jarvis\Command\Editor;

use Herrera\Json\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends Command
{
    use \Psr\Log\LoggerAwareTrait;
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;

    /**
     * @var string
     */
    private $editorSkeletonDir;

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * Sets the value of editor skeleton dir.
     *
     * @param string $dir the directory path
     *
     * @return self
     */
    public function setEditorSkeletonDir($dir)
    {
        $this->editorSkeletonDir = $dir;

        return $this;
    }

    /**
     * @param bool $bool
     */
    public function setEnabled($bool)
    {
        $this->enabled = $bool;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Install editor configuration');

        $this->addOption('editor', null, InputOption::VALUE_REQUIRED, 'Which editor to use', 'subl');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite configuration files');

        parent::configure();
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->editor = $input->getOption('editor');
        $this->overwrite = $input->getOption('force');
    }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($this->editor) {
            case 'subl':
                $this->setupEditorSublimeText($output);
                break;
            default:
                throw new \RuntimeException(sprintf('This editor "%s" is not supported yet', $this->editor));
        }
    }

    /**
     * Setup editor Sublime Text
     * @param  OutputInterface $output [description]
     * @return [type]                  [description]
     */
    protected function setupEditorSublimeText(OutputInterface $output)
    {
        $this->checkSublimeTextIsInstalled($output);
        $this->setupSublimeTextFileSettingsPreferences($output);
        $this->setupSublimeTextFileSettingsSublimeLinter($output);
        $this->setupSublimeTextFileSettingsPackageControl($output);
        $this->setupSublimeTextFileSettingsPhpCs($output);
        $this->setupSublimeTextFileSettingsGit($output);
    }

    /**
     * Check if Sublime Text 3 is installed.
     *
     * @param  OutputInterface $output
     * @throw \RuntimeException If Sublime Text 3 it is not installed
     */
    protected function checkSublimeTextIsInstalled(OutputInterface $output)
    {
        // Sublime Text 3 est-il déjà installé
        if (false === is_dir($this->getSublimeTextSettingDir())) {
            throw new \RuntimeException('Sublime Text 3 is not installed on your system. Please refer to the installation documentation at this address: http://sublime-text-unofficial-documentation.readthedocs.org/en/latest/getting_started/install.html');
        }
    }

    /**
     * Setup Sublime Text file settings preferences.
     *
     * @param  OutputInterface $output
     */
    protected function setupSublimeTextFileSettingsPreferences(OutputInterface $output)
    {
        $filename = 'Preferences.sublime-settings';

        $customConfig = array(
            'color_scheme' => null,
            'font_face' => null,
            'font_size' => null
        );

        $this->setupSublimeTextFileSettings($filename, $customConfig, $output);
    }

    /**
     * Setup Sublime Text file settings sublime linter package.
     *
     * @param  OutputInterface $output
     */
    protected function setupSublimeTextFileSettingsSublimeLinter(OutputInterface $output)
    {
        $this->setupSublimeTextFileSettings('SublimeLinter.sublime-settings', [], $output);
    }

    /**
     * Setup Sublime Text file settings phpcs package.
     *
     * @param  OutputInterface $output
     */
    protected function setupSublimeTextFileSettingsPhpCs(OutputInterface $output)
    {
        $customConfig = array(
            'phpcs_php_path' => $this->getCommandPath('php'),
            'phpcs_executable_path' => $this->getCommandPath('phpcs'),
            'php_cs_fixer_executable_path' => $this->getCommandPath('php-cs-fixer'),
            'phpmd_executable_path' => $this->getCommandPath('phpmd')
        );

        $this->setupSublimeTextFileSettings('phpcs.sublime-settings', $customConfig, $output);
    }

    /**
     * Setup Sublime Text file settings package controle.
     *
     * @param  OutputInterface $output
     */
    protected function setupSublimeTextFileSettingsPackageControl(OutputInterface $output)
    {
        $this->setupSublimeTextFileSettings('Package Control.sublime-settings', [], $output);
    }

    /**
     * Setup Sublime Text file settings git package.
     *
     * @param  OutputInterface $output
     */
    protected function setupSublimeTextFileSettingsGit(OutputInterface $output)
    {
        $this->setupSublimeTextFileSettings('SublimeGit.sublime-settings', [], $output);
    }

    private function getSublimeTextSkeletonUserDir()
    {
        return sprintf('%s/SublimeText3/Packages/User', $this->editorSkeletonDir);
    }

    private function getSublimeTextSettingUserDir()
    {
        return sprintf('%s/User', $this->getSublimeTextSettingDir());
    }

    private function getSublimeTextSettingDir()
    {
        $dir = getenv('HOME').'/.config/sublime-text-3/Packages';

        # Check environment OS (OSX, Linux)
        if ('Darwin' == php_uname('s')) {
            $dir = getenv('HOME').'/Library/Application Support/Sublime Text 3/Packages';
        }

        return $dir;
    }

    /**
     * Get array configuration from json file.
     *
     * @param  string $filepath
     */
    private function getConfigFromFile($filepath)
    {
        return  $this->decodeJsonFile($filepath);
    }

    /**
     * Save config in file.
     *
     * @param  string $filepath
     * @param  array  $config
     */
    private function saveConfigInFile($filepath, array $config)
    {
        if (false === file_exists($filepath)) {
            touch($filepath);
        }

        if (defined('JSON_UNESCAPED_SLASHES') && defined('JSON_PRETTY_PRINT')) {
            $json = json_encode($config, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        } else {
            $json = str_replace('\/', '/', json_encode($config));
        }

        file_put_contents($filepath, $json);
    }

    /**
     * Decode json file
     *
     * @param  string  $filepath
     * @param  boolean $assoc
     * @param  integer $depth
     * @param  integer $options
     * @return array
     */
    private function decodeJsonFile($filepath)
    {
        $json = file_get_contents($filepath);
        // search and remove comments like /* */ and //
        $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);

        $jsonReader = new Json();
        $assoc = true;
        $depth = 512;
        $options = 0;
        return $jsonReader->decode($json, $assoc, $depth, $options);
    }

    /**
     * Get command path.
     *
     * @param  string $cmd
     * @return string
     */
    private function getCommandPath($cmd)
    {
        $path = shell_exec("which $cmd");
        if ($path) {
            $path = trim($path);
        }
        return $path;
    }

    /**
     * Setup Sublime Text file settings
     *
     * @param  string          $filename
     * @param  array           $customConfig
     * @param  OutputInterface $output
     */
    private function setupSublimeTextFileSettings($filename, array $customConfig = array(), OutputInterface $output)
    {
        $output->writeln(sprintf('<comment>Updating the configuration file <info>%s</info></comment>', $filename));

        $originFile = sprintf('%s/%s', $this->getSublimeTextSkeletonUserDir(), $filename);
        if (!file_exists($originFile)) {
            throw new \RuntimeException(sprintf('File %s does not exist.', $originFile));
        }

        $targetFile = sprintf('%s/%s', $this->getSublimeTextSettingUserDir(), $filename);

        $backupFile = sprintf('%s/%s.backup', $this->getSublimeTextSettingUserDir(), $filename);

        if (file_exists($targetFile)) {
            $oldConfig = $this->getConfigFromFile($targetFile);
            foreach ($customConfig as $name => $value) {
                if (null === $value && isset($oldConfig[$name])) {
                    $customConfig[$name] = $oldConfig[$name];
                }
            }
        }

        if (count($customConfig)) {
            $output->writeln('');
            $output->writeln('Keeping Personal settings');
            foreach ($customConfig as $name => $value) {
                $output->writeln(sprintf('- %s: <comment>%s</comment>', $name, $value));
            }
            $output->writeln('');
        }

        $newConfig = $this->getConfigFromFile($originFile);

        $mergeConfig = array_merge(
            $newConfig,
            $customConfig
        );

        // Backup
        if (file_exists($targetFile)) {
            $output->writeln(sprintf('Backing up the previous version in the file <comment>%s</comment>', $backupFile));
            $this->getLocalFilesystem()->copy($targetFile, $backupFile, true);
        }

        $output->writeln(sprintf('Updated file <comment>%s</comment>', $targetFile));

        $this->saveConfigInFile($targetFile, $mergeConfig);

        $output->writeln('');
    }
}
