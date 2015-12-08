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

namespace Jarvis\Ustring;

class Search
{
    /**
     * Perform a fuzzy string searching.
     *
     * @param string $searchSet is an array of possible matches (strings)
     * @param string $query     is the string to search for
     *
     * @return array return a map with term match and score
     */
    public function fuzzy($searchSet, $query)
    {
        $results = [];

        $query = strtolower($query);

        foreach ($searchSet as $word) {
            if ($word == $query) { // has exact match
                $results[] = [
                    'match' => $word,
                    'score' => 0,
                ];

                return $results;
            }

            $pos = strpos($word, $query);
            if (false !== $pos) {
                $results[] = [
                    'match' => $word,
                    'score' => $pos,
                ];
            }
        }

        if (0 === count($results)) {
            // Fuzzy search implements a fuzzy string search in the style of Sublime Text
            $tokens = str_split($query);
            foreach ($searchSet as $word) {
                $tokenIndex = 0;
                $score = 0;
                $word = strtolower($word);
                foreach (str_split($word) as $wordIndex => $letter) {
                    if (isset($tokens[$tokenIndex]) && $letter === $tokens[$tokenIndex]) {
                        ++$tokenIndex;
                        ++$score;
                        if ($tokenIndex >= count($tokens)) {
                            $results[] = [
                                'match' => $word,
                                'score' => $score,
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return $results;
    }
}
