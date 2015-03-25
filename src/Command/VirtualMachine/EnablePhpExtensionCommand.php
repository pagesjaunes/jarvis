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

namespace Jarvis\Command\VirtualMachine;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Ssh\SshExecAwareTrait;

class EnablePhpExtensionCommand extends BaseCommand
{
    use SshExecAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Enables PHP extension in virtual machine');

        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Extension name'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getSshExec()->run(sprintf('sudo php5enmod %s', $input->getArgument('name')), $output);

        $this->getSshExec()->run('sudo service php5-fpm restart', $output);

        if ($this->getSshExec()->getLastReturnStatus() == 0) { // EXIT_SUCCESS
            $output->writeln(sprintf(
                '<info>PHP extension "%s" is enabled in virtual machine</info>',
                $input->getArgument('name')
            ));
        }

        return $this->getSshExec()->getLastReturnStatus();

        // $output->writeln($this->executeVagrantcommand('halt'), OutputInterface::OUTPUT_RAW);

        // system('ls -lsa');

        // passthru('ls -lsa');
        // passthru('ssh vagrant@127.0.0.1 -p 2222 -i '.$cwd.'/.vagrant/machines/default/virtualbox/private_key -t -q "ls -lsa"');
        // $commandLine = 'ssh vagrant@127.0.0.1 -p 2222 -i '.$cwd.'/.vagrant/machines/default/virtualbox/private_key -t -q "php /srv/www/front-pj-lr/front-pj-lr-webapp/app/console"';
// $connection = ssh2_connect('127.0.0.1', 2222);
// ssh2_auth_password($connection, 'vagrant', 'vagrant');

// // $stream = ssh2_shell($connection, 'vt102', null, 80, 24, SSH2_TERM_UNIT_CHARS);
// $stdout = ssh2_exec($connection, 'php /srv/www/front-pj-lr/front-pj-lr-webapp/app/console');
// $stderr = ssh2_fetch_stream($stdout, SSH2_STREAM_STDERR);
// stream_set_blocking($stderr, true);
// stream_set_blocking($stdout, true);
// $error = stream_get_contents($stderr);
// if ($error !== '') {
//     throw new RuntimeException($error);
// }
// echo stream_get_contents($stdout);
        // echo $this->executeRemoteCommand('php /srv/www/front-pj-lr/front-pj-lr-webapp/app/console', $output);
        // echo $this->executeRemoteCommand('php /srv/www/front-pj-lr/front-pj-lr-webapp/app/console');
        // $this->executeRemoteCommand('php /srv/www/front-pj-lr/front-pj-lr-webapp/app/console');
        // $this->executeRemoteCommand('php -i | grep "xdebug"', $output);

// TODO: $this->get('remote_process_builder')->getProcess()->run();
//
        // $this->executeRemoteCommand('sudo php5dismod xdebug');
        // echo $this->executeRemoteCommand('php -i | grep "xdebug"');
        // echo $this->executeRemoteCommand('php5-fpm -i | grep "xdebug"');
        // $this->executeRemoteCommand('sudo php5enmod xdebug');
        // echo $this->executeRemoteCommand('php -i | grep "xdebug"');
        // echo $this->executeRemoteCommand('php5-fpm -i | grep "xdebug"');
        // $this->executeRemoteCommand('sudo php5dismod xdebug');
        // echo $this->executeRemoteCommand('php -i | grep "xdebug"');
        // echo $this->executeRemoteCommand('php5-fpm -i | grep "xdebug"');

        // $builder = new ProcessBuilder(explode(' ', $commandLine));
        // $process = $builder->getProcess();
        // $process->run();
        // $output->writeln($process->getOutput(), OutputInterface::OUTPUT_RAW);
    }
}
