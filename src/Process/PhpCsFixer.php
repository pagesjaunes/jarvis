<?php

namespace Jarvis\Process;

use Herrera\Json\Json;
use JakubOnderka\PhpConsoleColor\ConsoleColor;
use JakubOnderka\PhpConsoleHighlighter\Highlighter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PhpCsFixer
{
    use \Jarvis\Ssh\SshExecAwareTrait;
    use \Jarvis\Filesystem\LocalFilesystemAwareTrait;
    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    private $localPhpcsStandardDir;
    private $remotePhpcsStandardDir;

    private $cacheDir;

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

    /**
     * Sets the value of localPhpcsStandardDir.
     *
     * @param mixed $localPhpcsStandardDir the local phpcs standard forbidden function names
     *
     * @return self
     */
    public function setLocalPhpcsStandardDir($localPhpcsStandardDir)
    {
        $this->localPhpcsStandardDir = $localPhpcsStandardDir;

        return $this;
    }

    /**
     * Sets the value of remotePhpcsStandardDir.
     *
     * @param mixed $remotePhpcsStandardDir the remote phpcs standard forbidden function names
     *
     * @return self
     */
    public function setRemotePhpcsStandardDir($remotePhpcsStandardDir)
    {
        $this->remotePhpcsStandardDir = $remotePhpcsStandardDir;

        return $this;
    }

    public function fixRemoteDir($remoteDir, OutputInterface $output, array $options = [])
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'dry-run' => true
        ]);

        $options = $resolver->resolve($options);

        $this->getSshExec()->passthru(
            strtr(
                'php-cs-fixer fix %command_options% --no-interaction %dir%',
                [
                    '%dir%' => $remoteDir,
                    '%command_options%' => $options['dry-run'] ? '--dry-run --diff' : ''
                ]
            )
        );

        $returnStatus = $this->getSshExec()->getLastReturnStatus();

        $this->getRemoteFilesystem()->mkdir($this->remotePhpcsStandardDir);

        $tmpDir = sprintf('%s/jarvis/phpcs_standard_dir', $this->cacheDir);
        $this->getLocalFilesystem()->mirror($this->localPhpcsStandardDir, $tmpDir);

        $this->getRemoteFilesystem()->syncLocalToRemote(
            $tmpDir,
            $this->remotePhpcsStandardDir,
            [
                'delete' => true
            ]
        );

        return $returnStatus;
    }
}
