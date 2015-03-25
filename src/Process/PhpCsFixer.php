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
    use \Jarvis\Filesystem\RemoteFilesystemAwareTrait;

    private $localPhpcsStandardDir;
    private $remotePhpcsStandardDir;

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

    public function selfUpdate()
    {
        return $this->getSshExec()->exec('composer global require fabpot/php-cs-fixer squizlabs/php_codesniffer');
    }

    public function fixRemoteDir($remoteDir, OutputInterface $output, array $options = [])
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'level' => 'symfony',
            'dry-run' => true
        ]);

        $options = $resolver->resolve($options);

        $this->getSshExec()->exec(
            strtr(
                'php-cs-fixer fix %command_options% --level=%level% --no-interaction %dir%',
                [
                    '%level%' => $options['level'],
                    '%dir%' => $remoteDir,
                    '%command_options%' => $options['dry-run'] ? '--dry-run --diff' : ''
                ]
            )
        );

        $returnStatus = $this->getSshExec()->getLastReturnStatus();

        $this->getRemoteFilesystem()->mkdir($this->remotePhpcsStandardDir);

        $this->getRemoteFilesystem()->syncLocalToRemote(
            $this->localPhpcsStandardDir,
            $this->remotePhpcsStandardDir,
            [
                'delete' => true
            ]
        );

        ob_start();
        $this->getSshExec()->exec(
            strtr(
                'phpcs %dir% --extensions=php --standard=%standard% --warning-severity=%warning-severity% --encoding=utf-8 --report=json',
                [
                    '%dir%' => $remoteDir,
                    '%standard%' => $this->remotePhpcsStandardDir,
                    '%warning-severity%' => 0
                ]
            )
        );
        $jsonReport = ob_get_clean();
        $json = new Json();
        // TODO: $json->validate($schema, $decoded); // throws Herrera\Json\Exception\JsonException
        try {
            $data = $json->decode($jsonReport);
        } catch (\Seld\JsonLint\ParsingException $e) {
            $output->writeln(sprintf('<error>%s</error>', $jsonReport));
            throw $e;
        }

        if ($data->totals->errors > 0) {
            $output->writeln(sprintf('<error>%s errors detected</error>', $data->totals->errors));
            foreach ($data->files as $filepath => $metadata) {
                if ($metadata->errors > 0) {
                    foreach ($metadata->messages as $error) {
                        $output->writeln(sprintf(
                            '<error>%s in "%s" at line %d</error>',
                            strtr($error->message, [' use NULL() instead' => '']),
                            strtr($filepath, [$remoteDir => '']),
                            $error->line
                        ));

                        $highlighter = new Highlighter(new ConsoleColor());

                        $fileContent = $this->getRemoteFilesystem()->getRemoteFileContent($filepath);
                        echo $highlighter->getCodeSnippet($fileContent, $error->line);
                    }
                }
            }
        }

        $returnStatus += $this->getSshExec()->getLastReturnStatus();

        return $returnStatus;
    }
}
