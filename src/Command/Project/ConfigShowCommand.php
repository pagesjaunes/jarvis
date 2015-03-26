<?php

namespace Jarvis\Command\Project;

use Symfony\Component\Console\Command\Command;
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

class ConfigShowCommand extends Command
{
    use ProjectConfigurationRepositoryAwareTrait;

    use AskProjectNameTrait;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add project in configuration');

        $this->addArgument('project_name', InputArgument::OPTIONAL, 'Project name');
    }

    protected function getProjectNamesToExclude()
    {
        return [];
    }

    /**
     * Gets all project names configured
     */
    protected function getAllProjectNames()
    {
        return $this->getProjectConfigurationRepository()->getProjectNames();
    }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $projectName = $input->getArgument('project_name') ?
            $input->getArgument('project_name')
            :
            $this->askProjectName($output, $this->getAllProjectNames(), $this->getProjectNamesToExclude())
        ;

        $config = $this->getProjectConfigurationRepository()->find($projectName);

        if (!$config) {
            $output->writeln(sprintf('<error>No project is found for name %s</error>', $input->getArgument('project_name')));
            return 1;
        }

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
            $output->writeln(sprintf(
                '<comment>%s: <info>%s</info></comment>',
                $propertyPath,
                $accessor->getValue($config, $propertyPath)
            ));
        }
    }

    protected function getProjectNamesToExclud()
    {
        return $this->getProjectConfigurationRepository()->getProjectAlreadyInstalledNames();
    }
}
