<?php

namespace Jarvis\Command\Project;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Jarvis\Project\ProjectConfiguration;
use Jarvis\Project\Repository\ProjectConfigurationRepositoryAwareTrait;

class ShowLocalDirectoryCommand extends BaseCommand
{
    use ProjectConfigurationRepositoryAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Show local git repository directory real path');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function getProjectNamesToExclude()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function executeCommandByProject($projectName, ProjectConfiguration $projectConfig, OutputInterface $output)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $output->write($accessor->getValue($projectConfig, 'local_git_repository_dir'));
    }
}
