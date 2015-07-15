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

# The following function is based on code from:
#
#   bash_completion - programmable completion functions for bash 3.2+
#
#   Copyright © 2006-2008, Ian Macdonald <ian@caliban.org>
#             © 2009-2010, Bash Completion Maintainers
#                     <bash-completion-devel@lists.alioth.debian.org>
#
#   This program is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2, or (at your option)
#   any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software Foundation,
#   Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
#   The latest version of this software can be obtained here:
#
#   http://bash-completion.alioth.debian.org/
#
#   RELEASE: 2.x

# This function can be used to access a tokenized list of words
# on the command line:
#
#   __git_reassemble_comp_words_by_ref '=:'
#   if test "\${words_[cword_-1]}" = -w
#   then
#       ...
#   fi
#
# The argument should be a collection of characters from the list of
# word completion separators (COMP_WORDBREAKS) to treat as ordinary
# characters.
#
# This is roughly equivalent to going back in time and setting
# COMP_WORDBREAKS to exclude those characters.  The intent is to
# make option types like --date=<type> and <rev>:<path> easy to
# recognize by treating each shell word as a single token.
#
# It is best not to set COMP_WORDBREAKS directly because the value is
# shared with other completion scripts.  By the time the completion
# function gets called, COMP_WORDS has already been populated so local
# changes to COMP_WORDBREAKS have no effect.
#
# Output: words_, cword_, cur_.
function_exists __git_reassemble_comp_words_by_ref ||
__git_reassemble_comp_words_by_ref()
{
    local exclude i j first
    # Which word separators to exclude?
    exclude="\${1//[^\$COMP_WORDBREAKS]}"
    cword_=\$COMP_CWORD
    if [ -z "\$exclude" ]; then
        words_=("\${COMP_WORDS[@]}")
        return
    fi
    # List of word completion separators has shrunk;
    # re-assemble words to complete.
    for ((i=0, j=0; i < \${#COMP_WORDS[@]}; i++, j++)); do
        # Append each nonempty word consisting of just
        # word separator characters to the current word.
        first=t
        while
            [ \$i -gt 0 ] &&
            [ -n "\${COMP_WORDS[\$i]}" ] &&
            # word consists of excluded word separators
            [ "\${COMP_WORDS[\$i]//[^\$exclude]}" = "\${COMP_WORDS[\$i]}" ]
        do
            # Attach to the previous token,
            # unless the previous token is the command name.
            if [ \$j -ge 2 ] && [ -n "\$first" ]; then
                ((j--))
            fi
            first=
            words_[\$j]=\${words_[j]}\${COMP_WORDS[i]}
            if [ \$i = \$COMP_CWORD ]; then
                cword_=\$j
            fi
            if ((\$i < \${#COMP_WORDS[@]} - 1)); then
                ((i++))
            else
                # Done.
                return
            fi
        done
        words_[\$j]=\${words_[j]}\${COMP_WORDS[i]}
        if [ \$i = \$COMP_CWORD ]; then
            cword_=\$j
        fi
    done
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
