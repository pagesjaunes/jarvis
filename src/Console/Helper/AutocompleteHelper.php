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
 */

namespace Jarvis\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

class AutocompleteHelper extends Helper
{
    /**
     * @var string
     */
    protected $autocompleteScript = <<<EOL
#!/bin/sh

function %%COMPLETE_FUNCTION_NAME%%() {
    local cur prev coms opts
    COMPREPLY=()
    cur="\${COMP_WORDS[COMP_CWORD]}"
    prev="\${COMP_WORDS[COMP_CWORD-1]}"
    coms="%%COMMANDS%%"
    opts="%%SHARED_OPTIONS%%"

    if [[ \${COMP_CWORD} = 1 ]] ; then
        COMPREPLY=($(compgen -W "\${coms}" -- \${cur}))

        return 0
    fi

    case "\${prev}" in
        %%SWITCH_CONTENT%%
        esac

    COMPREPLY=($(compgen -W "\${opts}" -- \${cur}))

    return 0;
}
complete -F %%COMPLETE_FUNCTION_NAME%% %%COMPLETE_COMMAND%%
EOL;
// COMP_WORDBREAKS=\${COMP_WORDBREAKS//:}
    /**
     * @param array $commands
     *
     * @return string
     */
    public function getAutoCompleteScript($completeFunctionName, $completeCommand, array $commands)
    {
        $dump = [];

        foreach ($commands as $command) {
            $options = [];
            foreach ($command['definition']['options'] as $option) {
                $options[] = (string) $option['name'];
            }

            $dump[$command['name']] = $options;
        }

        $commonOptions = [];
        foreach ($dump as $command => $options) {
            if (empty($commonOptions)) {
                $commonOptions = $options;
            }

            $commonOptions = array_intersect($commonOptions, $options);
        }

        $dump = array_map(
            function ($options) use ($commonOptions) {
                return array_diff($options, $commonOptions);
            },
            $dump
        );

        $switchCase = <<<SWITCHCASE
    %%COMMAND%%)
            opts="\${opts} %%COMMAND_OPTIONS%%"
            ;;
SWITCHCASE;

        $switchContent = '';
        foreach ($dump as $command => $options) {
            if (empty($options)) {
                continue;
            }

            $switchContent .= str_replace(
                [
                    '%%COMMAND%%',
                    '%%COMMAND_OPTIONS%%'
                ],
                [
                    $command,
                    join(' ', $options)
                ],
                $switchCase
            );
        }

        return str_replace(
            [
                '%%COMPLETE_FUNCTION_NAME%%',
                '%%COMPLETE_COMMAND%%',
                '%%COMMANDS%%',
                '%%SHARED_OPTIONS%%',
                '%%SWITCH_CONTENT%%'
            ],
            [
                $completeFunctionName,
                $completeCommand,
                implode(' ', array_column($commands, 'name')),
                implode(' ', $commonOptions),
                $switchContent
            ],
            $this->autocompleteScript
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'autocomplete';
    }
}
