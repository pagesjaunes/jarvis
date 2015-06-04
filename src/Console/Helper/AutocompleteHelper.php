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

# source: https://github.com/juven/maven-bash-completion/blob/master/bash_completion.bash
function_exists()
{
    declare -F $1 > /dev/null
    return $?
}

# source: https://github.com/juven/maven-bash-completion/blob/master/bash_completion.bash
function_exists _get_comp_words_by_ref ||
_get_comp_words_by_ref ()
{
    local exclude cur_ words_ cword_;
    if [ "$1" = "-n" ]; then
        exclude=$2;
        shift 2;
    fi;
    __git_reassemble_comp_words_by_ref "\$exclude";
    cur_=\${words_[cword_]};
    while [ \$# -gt 0 ]; do
        case "\$1" in
            cur)
                cur=\$cur_
            ;;
            prev)
                prev=\${words_[\$cword_-1]}
            ;;
            words)
                words=("\${words_[@]}")
            ;;
            cword)
                cword=\$cword_
            ;;
        esac;
        shift;
    done
}

# source: https://github.com/juven/maven-bash-completion/blob/master/bash_completion.bash
function_exists __ltrim_colon_completions ||
__ltrim_colon_completions()
{
    if [[ "\$1" == *:* && "\$COMP_WORDBREAKS" == *:* ]]; then
        # Remove colon-word prefix from COMPREPLY items
        local colon_word=\${1%\${1##*:}}
        local i=\${#COMPREPLY[*]}
        while [[ \$((--i)) -ge 0 ]]; do
            COMPREPLY[\$i]=\${COMPREPLY[\$i]#"\$colon_word"}
        done
    fi
}

function %%COMPLETE_FUNCTION_NAME%%() {
    local cur prev coms opts
    COMPREPLY=()
    _get_comp_words_by_ref -n : cur prev
    coms="%%COMMANDS%%"
    opts="%%SHARED_OPTIONS%%"

    if [[ \${cur} == *:* ]] ; then
        COMPREPLY=(\$(compgen -W "\${coms}" -S ' ' -- \${cur}))
        __ltrim_colon_completions "\$cur"
        return 0
    elif [[ \${COMP_CWORD} = 1 ]] ; then
        COMPREPLY=(\$(compgen -W "\${coms}" -- \${cur}))
        __ltrim_colon_completions "\$cur"
        return 0
    fi

    case "\${prev}" in
        %%SWITCH_CONTENT%%
        esac

    COMPREPLY=($(compgen -W "\${opts}" -- \${cur}))

    __ltrim_colon_completions "\$cur"

    return 0;
}

complete -F %%COMPLETE_FUNCTION_NAME%% %%COMPLETE_COMMAND%%
EOL;

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
