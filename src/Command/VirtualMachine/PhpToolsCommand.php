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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Jarvis\PhpTool\PhpToolManager;

class PhpToolsCommand extends BaseCommand
{
    /**
     * @var PhpToolManager
     */
    protected $phpToolManager;

    /**
     * @param PhpToolManager $manager
     */
    public function setPhpToolManager(PhpToolManager $manager)
    {
        $this->phpToolManager = $manager;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Installing PHP tools');

        $this->addArgument(
            'names',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'What tools you want to install (separate multiple names with a space)'
        );

        $this->addOption(
            'all',
            null,
            InputOption::VALUE_NONE,
            'Install all PHP tools'
        );

        $this->addOption(
            'reinstall',
            null,
            InputOption::VALUE_NONE,
            'Reinstall all PHP tools'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $names = $this->getPhpToolNames($input, $output);

        $exitStatus = 0;
        switch ($this->action) {
            case 'install':
                if (empty($names)) {
                    $output->writeln('<info>Nothing to install</info>');
                } else {
                    foreach ($names as $name) {
                        $output->writeln('Install <info>'.$name.'</info>');
                        $exitStatus = $this->phpToolManager->install($name, $output);
                    }
                }
                break;
            case 'update':
                foreach ($names as $name) {
                    $output->writeln('Update <info>'.$name.'</info>');
                    $exitStatus = $this->phpToolManager->update($name, $output);
                }
                break;

            case 'version':
                foreach ($names as $name) {
                    $output->writeln('Display current version for <info>'.$name.'</info>');
                    $output->writeln($this->phpToolManager->version($name, $output));
                }
                break;

            case 'status':
                foreach ($names as $name) {
                    $output->write('<info>'.$name.'</info>: ');
                    $output->writeln($this->phpToolManager->isAlreadyInstalled($name, $output) ?
                        'installed'
                        :
                        'not installed'
                    );
                }
                break;

            default:
                throw new \InvalidArgumentException(sprintf(
                    'This %s action does\'nt exist',
                    $this->action
                ));
        }

        return $exitStatus;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     */
    protected function getPhpToolNames(InputInterface $input, OutputInterface $output)
    {
        if (true === $input->getOption('all')) {
            $names = [];
            if ('install' == $this->action) {
                foreach ($this->phpToolManager->getAllPhpToolsNames() as $name) {
                    if (
                        true === $input->getOption('reinstall')
                        ||
                        false === $this->phpToolManager->isAlreadyInstalled($name, $output)
                    ) {
                        $names[] = $name;
                    }
                }

                return $names;
            }

            return $this->phpToolManager->getAllPhpToolsNames();
        }

        $names = $input->getArgument('names');

        if (empty($names)) {
            $toolsNamesToExclude = [];

            if ('install' == $this->action && false === $input->getOption('reinstall')) {
                $toolsNamesToExclude = $this->phpToolManager->getAllAlreadyInstalledPhpToolsNames($output);
            }

            $names = $this->askPhpToolName(
                $input,
                $output,
                $this->phpToolManager->getAllPhpToolsNames(),
                $toolsNamesToExclude
            );
        }

        return $names;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $allToolsNames
     * @param array           $toolsNamesToExclude
     *
     * @return string
     *
     * @throws \RuntimeException If PHP tool is not found
     */
    protected function askPhpToolName(InputInterface $input, OutputInterface $output, array $allToolsNames, array $toolsNamesToExclude = [])
    {
        $helper = $this->getHelper('question');

        $choices = count($toolsNamesToExclude) ?
            array_values(array_diff($allToolsNames, $toolsNamesToExclude))
            :
            $allToolsNames
        ;

        if (count($choices) == 0) {
            $question = new ConfirmationQuestion('All PHP tools are already installed. Do you want force installation for all tools? (<info>y</info>/n; default do nothing) ', false);

            if (!$helper->ask($input, $output, $question)) {
                return [];
            }

            $output->writeln(' ');

            return $allToolsNames;
        }

        $default = implode(',', $choices);

        $question = new ChoiceQuestion(
            'Please select what PHP tool to install (defaults to all names; separate multiple names with a comma; autocompletion is active)',
            $choices,
            $default
        );
        $question->setMultiselect(true);
        $question->setAutocompleterValues($allToolsNames);

        $names = $helper->ask($input, $output, $question);

        $output->writeln('You have just selected: <info>'.implode(', ', $names).'</info>');

        return $names;
    }
}
