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

namespace Jarvis\PhpTool;

use Symfony\Component\Console\Output\OutputInterface;

class PhpToolManager
{
    use \Jarvis\Ssh\SshExecAwareTrait;

    /**
     * @var array
     */
    protected $phpTools;

    public function __construct(array $tools)
    {
        $this->phpTools = $tools;
    }

    /**
     * @param  string $name Project name
     * @param  OutputInterface|null $output
     *
     * @return integer Exit status
     */
    public function install($name, OutputInterface $output = null)
    {
        $data = $this->getData($name);

        if (isset($data['install_command'])) {
            $this->getSshExec()->run(strtr(
                $data['install_command'],
                [
                    '{{name}}' => $name,
                    '{{url}}' => $data['url'],
                    '{{dest}}' => $data['dest'],
                    '{{version_option}}' => $data['version_option'],
                ]
            ), $output);

            return $this->getSshExec()->getLastReturnStatus();
        }

        $extension = pathinfo($data['url'], PATHINFO_EXTENSION);
        if ($extension == 'phar') {
            $this->getSshExec()->run(strtr(
                'wget --output-document=/tmp/{{name}} --no-verbose {{url}} && sudo mv /tmp/{{name}} {{dest}} && sudo chmod +x {{dest}} {{version_command}}',
                [
                    '{{name}}' => $name,
                    '{{url}}' => $data['url'],
                    '{{dest}}' => $data['dest'],
                    '{{version_command}}' => $data['version_option'] ? '&& ' .$data['dest'].' '.$data['version_option'] : '',
                ]
            ), $output);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The procedure of the installation is not designed for this extension "%s"',
                $extension
            ));
        }

        return $this->getSshExec()->getLastReturnStatus();
    }

    /**
     * @param  string $name Project name
     * @param  OutputInterface|null $output
     *
     * @return integer Exit status
     */
    public function update($name, OutputInterface $output = null)
    {
        $data = $this->getData($name);

        if (isset($data['update_command'])) {
            $this->getSshExec()->run(strtr(
                $data['update_command'],
                [
                    '{{name}}' => $name,
                    '{{url}}' => $data['url'],
                    '{{dest}}' => $data['dest'],
                    '{{version_option}}' => $data['version_option'],
                ]
            ), $output);

            return $this->getSshExec()->getLastReturnStatus();
        }

        return $this->install($name, $output);
    }

    /**
     * @param  string $name Project name
     * @param  OutputInterface|null $output
     *
     * @return integer Exit status
     */
    public function version($name, OutputInterface $output = null)
    {
        $data = $this->getData($name);

        if (isset($data['version_command'])) {
            return $this->getSshExec()->run(strtr(
                $data['version_command'],
                [
                    '{{name}}' => $name,
                    '{{url}}' => $data['url'],
                    '{{dest}}' => $data['dest'],
                    '{{version_option}}' => $data['version_option'],
                ]
            ), $output);

            return $this->getSshExec()->getLastReturnStatus();
        }

        if ($data['version_option']) {
            return $this->getSshExec()->run(strtr(
                '{{dest}} {{version_option}}',
                [
                    '{{dest}}' => $data['dest'],
                    '{{version_option}}' => $data['version_option'],
                ]
            ), $output);
        }

        return;
    }

    /**
     * @param string $name
     * @param  OutputInterface|null $output
     *
     * @return bool
     */
    public function isAlreadyInstalled($name, OutputInterface $output = null)
    {
        $data = $this->getData($name);

        $this->getSshExec()->exec(strtr(
            'command -v {{dest}} >/dev/null 2>&1',
            [
                '{{dest}}' => $data['dest'],
            ]
        ));

        return $this->getSshExec()->getLastReturnStatus() == 0;
    }

    /**
     * @return array
     */
    public function getAllPhpToolsNames()
    {
        return array_keys($this->phpTools);
    }

    /**
     * @param  OutputInterface|null $output
     *
     * @return array
     */
    public function getAllAlreadyInstalledPhpToolsNames(OutputInterface $output = null)
    {
        $names = [];
        foreach ($this->getAllPhpToolsNames() as $name) {
            if (true === $this->isAlreadyInstalled($name, $output)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param string $name
     *
     * @return array
     *
     * @throws \InvalidArgumentException If PHP tool doesn't exist
     */
    protected function getData($name)
    {
        if (!isset($this->phpTools[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'This PHP tools "%s" doesn\'t exist',
                $name
            ));
        }

        if (!array_key_exists('version_option', $this->phpTools[$name])) {
            $this->phpTools[$name]['version_option'] = false;
        }

        if (!array_key_exists('url', $this->phpTools[$name])) {
            $this->phpTools[$name]['url'] = null;
        }

        return $this->phpTools[$name];
    }
}
