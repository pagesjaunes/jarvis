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

namespace Jarvis\Vagrant\Configuration;

use Jarvis\Vagrant\Exec;

class SshConfiguration
{
    /**
     * @var Exec
     */
    private $exec;

    /**
     * @var null|array
     */
    private $parameters;

    public function __construct(Exec $exec, array $parameters = null)
    {
        $this->exec = $exec;
        $this->parameters = $parameters;
    }

    /**
     * Returns the parameters.
     *
     * @return array An array of parameters
     *
     * @api
     */
    public function all()
    {
        $this->init();

        return $this->parameters;
    }

    /**
     * Returns a parameter by name.
     *
     * @param string $path    The key
     * @param mixed  $default The default value if the parameter key does not exist
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function get($path, $default = null)
    {
        $this->init();

        return array_key_exists($path, $this->parameters) ? $this->parameters[$path] : $default;
    }

    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     *
     * @return bool true if the parameter exists, false otherwise
     *
     * @api
     */
    public function has($key)
    {
        $this->init();

        return array_key_exists($key, $this->parameters);
    }

    protected function init()
    {
        if (null === $this->parameters) {
            $this->parse();
        }
    }

    protected function parse()
    {
        $result = $this->exec->run(sprintf(
            'ssh-config %s',
            'default'
            // $input->getOption('name')
        ));

        $parts = explode("\n", $result);
        $parts = array_map('trim', $parts);

        $this->parameters = [];
        foreach ($parts as $part) {
            $subparts = explode(' ', $part);
            if (isset($subparts[1])) {
                $this->parameters[$subparts[0]] = $subparts[1];
            }
        }
    }
}
