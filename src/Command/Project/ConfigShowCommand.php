<?php

namespace Jarvis\Command\Project;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Jarvis\Command\Project\AskProjectNameTrait;
use Jarvis\Project\ProjectConfiguration;
use Jarvis\Project\Repository\ProjectConfigurationRepositoryAwareTrait;

class ConfigShowCommand extends BaseCommand
{
    use ProjectConfigurationRepositoryAwareTrait;

    use AskProjectNameTrait;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add project in configuration');

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

        foreach ([
            'project_name',
            'git_repository_url',
            'local_git_repository_dir',
            'remote_git_repository_dir',
            'git_target_branch',
            'remote_webapp_dir',
            'local_webapp_dir',
            'remote_vendor_dir',
            'local_vendor_dir',
            'remote_symfony_console_path',
            'remote_phpunit_configuration_xml_path',
        ] as $propertyPath) {
            $propertyValue = $accessor->getValue($projectConfig, $propertyPath);
            if (null !== $propertyValue) {
                $output->writeln(sprintf(
                    '<comment>%s: <info>%s</info></comment>',
                    $propertyPath,
                    $propertyValue
                ));
            }
        }
    }
}
