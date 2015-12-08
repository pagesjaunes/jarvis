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

namespace Jarvis\Command\Project;

use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Output\OutputInterface;
use Jarvis\Console\Helper\ReadlineHelper;
use Jarvis\Console\Readline\Autocompleter\Fuzzy as FuzzyAutocompleter;
use Jarvis\Ustring\Search;

trait AskProjectNameTrait
{
    protected function askProjectName(OutputInterface $output, array $choices)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $readline = new Readline();
        $helper = new ReadlineHelper($readline);

        $default = null;
        $keyAsValues = false;
        $multi = false;
        $autocompleter = new FuzzyAutocompleter([], new Search);

        $name = $helper->select(
            $output,
            'Select a value > ',
            $choices,
            $default,
            $keyAsValues,
            $multi,
            $autocompleter
        );

        $output->writeln('You have just selected: <info>'.$name.'</info>');

        return $name;
    }
}
