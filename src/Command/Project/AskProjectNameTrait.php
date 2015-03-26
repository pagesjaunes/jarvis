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

use Symfony\Component\Console\Output\OutputInterface;

trait AskProjectNameTrait
{
    protected function askProjectName(OutputInterface $output, array $allProjectNames, array $projectNamesToExclude = [])
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $projectNames = count($projectNamesToExclude) ?
            array_intersect($projectNamesToExclude, $allProjectNames)
            :
            $allProjectNames
        ;

        $projectName = $dialog->askAndValidate(
            $output,
            'Please enter the name of a project (autocompletion active): ', // question
            function ($answer) use ($projectNames) {
            // validator
                if (empty($answer)) {
                    throw new \RuntimeException('The project name is empty');
                }

                if (!in_array($answer, $projectNames)) {
                    throw new \RuntimeException(
                        sprintf('The name "%s" of the project does\'t exist', $answer)
                    );
                }

                return $answer;
            },
            false, // Using false means the amount of attempts is infinite.
            null, // default
            $projectNames // autocomplete
        );

        return $projectName;
    }
}
