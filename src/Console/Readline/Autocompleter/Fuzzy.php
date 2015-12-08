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

namespace Jarvis\Console\Readline\Autocompleter;

use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Jarvis\Ustring\Search;

/**
 * Class \Hoa\Console\Readline\Autocompleter\Word.
 *
 * The fuzzy auto-completer: complete a word.
 */
class Fuzzy implements Autocompleter
{
    /**
     * List of words.
     *
     * @var array
     */
    protected $words;

    /**
     * @var Search
     */
    protected $search;

    /**
     * Constructor.
     *
     * @param array $words Words.
     */
    public function __construct(array $words, Search $search)
    {
        $this->words = $words;
        $this->search = $search;

        return;
    }

    /**
     * Complete a word.
     * Returns null for no word, a full-word or an array of full-words.
     *
     * @param string &$prefix Prefix to autocomplete.
     *
     * @return mixed
     */
    public function complete(&$prefix)
    {
        $out = [];
        foreach ($this->search->fuzzy($this->words, $prefix) as $result) {
            $out[$result['match']] =  $result['score'];
        }
        asort($out);

        if (0 === count($out)) {
            return;
        }

        if (1 === count($out)) {
            return key($out);
        }

        return array_keys($out);
    }

    /**
     * Get definition of a word.
     *
     * @return string
     */
    public function getWordDefinition()
    {
        return '\b(\w|\-|_)+\b';
    }

    /**
     * Set list of words.
     *
     * @param array $words Words.
     */
    public function setWords(Array $words)
    {
        $this->words = $words;
    }
}
