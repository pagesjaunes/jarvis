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

namespace Jarvis\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Process\Process;
use Jarvis\Process\Exec;
use Jarvis\Ssh\Exec as SshExec;

class RemoteFilesystem
{
    use \Psr\Log\LoggerAwareTrait;

    /**
     * @var Exec
     */
    protected $exec;

    /**
     * @var SshExec
     */
    protected $sshExec;

    /**
     * Constructor.
     *
     * @param Exec $exec
     * @param SshExec $sshExec
     */
    public function __construct(Exec $exec, SshExec $sshExec)
    {
        $this->exec = $exec;
        $this->sshExec = $sshExec;
    }

    /**
     * Returns the home directory for ssh user.
     *
     * @return string
     */
    public function getHomeDirectory()
    {
        return strtr(
            '/home/%ssh_username%',
            [
                '%ssh_username%' => $this->sshExec->getOption('ssh_user')
            ]
        );
    }

    /**
     * Checks the existence of files or directories.
     *
     * @param string|array|\Traversable $files A filename, an array of files, or a \Traversable instance to check
     *
     * @return bool true if the file exists, false otherwise
     */
    public function exists($files)
    {
        foreach ($this->toIterator($files) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION)) {
                if (!$this->isFileExist($file)) {
                    return false;
                }
            }

            if (!$this->isDirExist($file)) {
                return false;
            }
        }
    }

    /**
     * Reads entire remote file into a string
     *
     * @param  string $filepath Path of the file to read.
     * @return string
     */
    public function getRemoteFileContent($filepath)
    {
        $commandLine = strtr(
            'test -f %file_path% && cat %file_path% || echo ""',
            [
                '%file_path%' => $filepath
            ]
        );

        return $this->sshExec->exec($commandLine);
    }

    /**
     * @param  string $dir
     *
     * @return bool
     */
    public function remove($filepath)
    {
        $commandLine = null;
        if ($this->isDirExist($filepath)) {
            $commandLine = sprintf('rm -fr %s', $filepath);
        } elseif ($this->isFileExist($filepath)) {
            $commandLine = sprintf('rm -%s', $filepath);
        } else {
            return 0;
        }

        $this->sshExec->exec($commandLine);

        if ($this->sshExec->getLastReturnStatus() !== 0) {
            !$this->logger ?: $this->logger->error(sprintf('Error remove %s', $filepath));
        }

        return $this->sshExec->getLastReturnStatus() === 0;
    }

    /**
     * @param  string $dir
     *
     * @return bool
     */
    public function mkdir($dir)
    {
        $this->sshExec->exec(sprintf('mkdir -p %s', $dir));

        if ($this->sshExec->getLastReturnStatus() !== 0) {
            !$this->logger ?: $this->logger->error(sprintf('Error make directory %s', $dir));
        }

        return $this->sshExec->getLastReturnStatus() === 0;
    }

    /**
     * @param  string            $dir Path of the directory to check.
     * @return boolean
     * @throws \RuntimeException
     */
    public function isDirExist($dir)
    {
        $commandLine = strtr(
            'test -d  %dir% && echo exists 2>&1',
            [
                '%dir%' => $dir
            ]
        );

        $output = $this->sshExec->exec($commandLine);

        return 'exists' == trim($output);
    }

    /**
     * @param  string            $filepath Path of the file to check.
     * @return boolean
     * @throws \RuntimeException
     */
    public function isFileExist($filepath)
    {
        $commandLine = strtr(
            'test -f %file_path% && echo exists 2>&1',
            [
                '%file_path%' => $filepath
            ]
        );

        $output = $this->sshExec->exec($commandLine);

        return 'exists' == trim($output);
    }

    /**
     * Copies a file.
     *
     * This method only copies the file if the origin file is newer than the target file.
     *
     * By default, if the target already exists, it is not overridden.
     *
     * @param string $originFile The original filename
     * @param string $targetFile The target filename
     * @param bool   $override   Whether to override an existing file or not
     *
     * @throws FileNotFoundException When originFile doesn't exist
     *
     * @return exit code
     */
    public function copy($originFile, $targetFile, $override = false)
    {
        if (!$this->isFileExist($originFile)) {
            throw new FileNotFoundException(sprintf('Failed to copy "%s" because file does not exist.', $originFile), 0, null, $originFile);
        }

        $this->sshExec->exec(strtr(
            'mkdir -p %targetDir% && cp -R %override% %originFile% %targetFile%', [
            '%originFile%' => $originFile,
            '%targetDir%' => dirname($targetFile),
            '%targetFile%' => $targetFile,
            '%override%' => $override ? '' : '-n'
        ]));

        if ($this->sshExec->getLastReturnStatus() !== 0) {
            !$this->logger ?: $this->logger->error(sprintf('Error copy %s to %s', $originFile, $targetFile));
        }

        return $this->sshExec->getLastReturnStatus();
    }

    /**
     * Copies a file.
     *
     * This method only copies the file if the local file is newer than the target file.
     *
     *
     * @param string $remoteFile The remote filename
     * @param string $localFile The local filename
     *
     * @throws FileNotFoundException When localFile doesn't exist
     *
     * @return exit code
     */
    public function copyRemoteFileToLocal($remoteFile, $localFile)
    {
        if (!$this->isFileExist($remoteFile)) {
            throw new FileNotFoundException(sprintf('Failed to copy "%s" because file does not exist.', $remoteFile), 0, null, $remoteFile);
        }

        $this->exec->run(strtr(
            'scp -P %ssh_port% %ssh_identity_file_option% %ssh_username%@%ssh_host%:%remoteFile% %localFile%',
            [
                '%ssh_username%' => $this->sshExec->getOption('ssh_user'),
                '%ssh_host%' => $this->sshExec->getOption('ssh_host'),
                '%ssh_port%' => $this->sshExec->getOption('ssh_port'),
                '%ssh_identity_file_option%' => $this->sshExec->getOption('ssh_identity_file') ? '-i '.$this->sshExec->getOption('ssh_identity_file') : '',
                '%remoteFile%' => $remoteFile,
                '%localFile%' => $localFile,
            ]
        ));

        if ($this->exec->getLastReturnStatus() !== 0) {
            !$this->logger ?: $this->logger->error(sprintf('Error copy %s to %s', $remoteFile, $localFile));
        }

        return $this->exec->getLastReturnStatus();
    }

    /**
     * Copies a file.
     *
     * This method only copies the file if the local file is newer than the target file.
     *
     * @param string $localFile The local filename
     * @param string $remoteFile The remote filename
     *
     * @throws FileNotFoundException When localFile doesn't exist
     *
     * @return exit code
     */
    public function copyLocalFileToRemote($localFile, $remoteFile)
    {
        $this->exec->run(strtr(
            'test -f %localFile% && scp -P %ssh_port% %ssh_identity_file_option% %localFile% %ssh_username%@%ssh_host%:%remoteFile%',
            [
                '%ssh_username%' => $this->sshExec->getOption('ssh_user'),
                '%ssh_host%' => $this->sshExec->getOption('ssh_host'),
                '%ssh_port%' => $this->sshExec->getOption('ssh_port'),
                '%ssh_identity_file_option%' => $this->sshExec->getOption('ssh_identity_file') ? '-i '.$this->sshExec->getOption('ssh_identity_file') : '',
                '%localFile%' => $localFile,
                '%remoteFile%' => $remoteFile
            ]
        ));

        if ($this->exec->getLastReturnStatus() !== 0) {
            !$this->logger ?: $this->logger->error(sprintf('Error copy %s to %s', $localFile, $remoteFile));
        }

        return $this->exec->getLastReturnStatus();
    }

    /**
     * Sync remote to local directory.
     *
     * @param string       $remoteDir The remote origin directory
     * @param string       $localDir The local target directory
     * @param array        $options   An array of boolean options
     *                                Valid options are:
     *                                - $options['override'] Whether to override an existing file on copy or not (see copy())
     *                                - $options['delete'] Whether to delete files that are not in the source directory (defaults to false)
     *
     * @return exit code
     */
    public function syncRemoteToLocal($remoteDir, $localDir, $options = array())
    {
        $commandLine = strtr(
            'rsync %delete% --recursive --checksum --compress %dry_run% %extra_rsync_options% --rsh \'ssh -p %ssh_port% %ssh_identity_file_option%\' %ssh_username%@%ssh_host%:%remote_dir%/ %local_dir%',
            [
                '%delete%' => isset($options['delete']) && $options['delete'] ? '--delete' : '',
                '%dry_run%' => isset($options['dry_run']) && $options['dry_run'] ? '--dry-run' : '',
                '%ssh_username%' => $this->sshExec->getOption('ssh_user'),
                '%ssh_host%' => $this->sshExec->getOption('ssh_host'),
                '%ssh_port%' => $this->sshExec->getOption('ssh_port'),
                '%ssh_identity_file_option%' => $this->sshExec->getOption('ssh_identity_file') ? '-i '.$this->sshExec->getOption('ssh_identity_file') : '',
                '%extra_rsync_options%' => '--verbose --human-readable --progress',
                '%remote_dir%' => $remoteDir,
                '%local_dir%' => $localDir
            ]
        );
        $this->exec->exec($commandLine);

        if ($this->exec->getLastReturnStatus() !== 0) {
            !$this->logger ?: $this->logger->error(sprintf(
                'Error sync remote to  %s to local %s',
                $remoteDir,
                $localDir
            ));
        }

        return $this->exec->getLastReturnStatus();
    }

    /**
     * Sync local to remote directory.
     *
     * @param string       $remoteDir The remote origin directory
     * @param string       $localDir The local target directory
     * @param array        $options   An array of boolean options
     *                                Valid options are:
     *                                - $options['override'] Whether to override an existing file on copy or not (see copy())
     *                                - $options['delete'] Whether to delete files that are not in the source directory (defaults to false)
     *
     * @return exit code
     */
    public function syncLocalToRemote($localDir, $remoteDir, $options = array())
    {
        $this->exec->run(strtr(
            'rsync %delete% --recursive --checksum --compress %extra_rsync_options% --rsh \'ssh -p %ssh_port% %ssh_identity_file_option%\' %local_dir%/ %ssh_username%@%ssh_host%:%remote_dir%',
            [
                '%delete%' => isset($options['delete']) && $options['delete'] ? '--delete' : '',
                '%ssh_username%' => $this->sshExec->getOption('ssh_user'),
                '%ssh_host%' => $this->sshExec->getOption('ssh_host'),
                '%ssh_port%' => $this->sshExec->getOption('ssh_port'),
                '%ssh_identity_file_option%' => $this->sshExec->getOption('ssh_identity_file') ? '-i '.$this->sshExec->getOption('ssh_identity_file') : '',
                '%extra_rsync_options%' => '--verbose --human-readable --progress',
                '%remote_dir%' => $remoteDir,
                '%local_dir%' => $localDir
            ]
        ));

        if ($this->exec->getLastReturnStatus() !== 0) {
            !$this->logger ?: $this->logger->error(sprintf(
                'Error sync local to  %s to remote %s',
                $localDir,
                $remoteDir
            ));
        }

        return $this->exec->getLastReturnStatus();
    }

    /**
     * @param mixed $files
     *
     * @return \Traversable
     */
    private function toIterator($files)
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }

        return $files;
    }
}
