<?php

namespace Jarvis\Command\Project;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Jarvis\Project\ProjectConfigurationFactory;
use Jarvis\Project\Repository\ProjectConfigurationRepositoryAwareTrait;

class ConfigAddCommand extends Command
{
    use ProjectConfigurationRepositoryAwareTrait;

    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @var ProjectConfigurationFactory
     */
    private $projectConfigurationFactory;

    /**
     * @param bool $bool
     */
    public function setEnabled($bool)
    {
        $this->enabled = $bool;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled && count($this->getProjectConfigurationRepository()->getProjectInstalledNames());
    }

    /**
     * Sets the value of projectConfigurationFactory.
     *
     * @param ProjectConfigurationFactory $projectConfigurationFactory the project configuration factory
     *
     * @return self
     */
    public function setProjectConfigurationFactory(ProjectConfigurationFactory $projectConfigurationFactory)
    {
        $this->projectConfigurationFactory = $projectConfigurationFactory;

        return $this;
    }

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add project in configuration');
    }

    /**
     * @{inheritdoc}
     */
    // protected function initialize(InputInterface $input, OutputInterface $output)
    // {
    // }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $data = [
            'project_name' => null,
            'git_repository_url' => null,
            'local_git_repository_dir' => '%local_projects_root_dir%/%project_name%', //
            'remote_git_repository_dir' => '%remote_projects_root_dir%/%project_name%',
            'git_target_branch' => 'develop',
            'remote_webapp_dir' => '%remote_projects_root_dir%/%project_name%',
            'local_webapp_dir' => '%local_projects_root_dir%/%project_name%',
            'remote_vendor_dir' => '/home/vagrant/projects/%project_name%/vendor',
            'local_vendor_dir' => '%local_vendor_root_dir%/%project_name%',
            'remote_symfony_console_path' => '%remote_vendor_root_dir%/app/console',
            'remote_phpunit_configuration_xml_path' => '%remote_vendor_root_dir%/app/phpunit.xml.dist',
        ];

        $helper = $this->getHelper('question');

        $question = new Question('Please enter the name of the project: ', '');
        $question->setValidator(function ($answer) {
            if ($this->getProjectConfigurationRepository()->find($answer)) {
                throw new \RuntimeException(
                    'The name of the project already exists'
                );
            }

            return $answer;
        });

        $data['project_name'] = $helper->ask($input, $output, $question);

        $question = new Question('Please enter the url of the git repository: ', '');
        $question->setValidator(function ($answer) {
            if (!filter_var($answer, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException(
                    'This value is not a valid URL.'
                );
            }

            if ($this->getProjectConfigurationRepository()->findBy(['git_repository_url' => $answer])) {
                throw new \RuntimeException(
                    'This value already exists.'
                );
            }

            return $answer;
        });

        $data['git_repository_url'] = $helper->ask($input, $output, $question);

        $config = $this->projectConfigurationFactory->create($data);

        foreach (array_keys($data) as $propertyPath) {
            if ($propertyPath == 'project_name' || $propertyPath == 'git_repository_url') {
                continue;
            }

            $value = $accessor->getValue($config, $propertyPath);

            $question = new Question(sprintf('Please enter the %s (<info>%s</info>): ', $propertyPath, $value), $value);
            $data[$propertyPath] = $helper->ask($input, $output, $question);
        }

        $config = $this->projectConfigurationFactory->create($data);

        $repository = $this->getProjectConfigurationRepository();
        $repository->add($config);

        $output->writeln('Add project configuration: ');

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
}
