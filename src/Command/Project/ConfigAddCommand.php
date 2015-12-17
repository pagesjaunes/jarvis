<?php

namespace Jarvis\Command\Project;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Jarvis\Command\Project\ConfigAddCommand;
use Jarvis\Project\Repository\ProjectConfigurationRepositoryAwareTrait;

class ConfigAddCommand extends Command
{
    use ProjectConfigurationRepositoryAwareTrait;

    /**
     * @var bool
     */
    protected $enabled = false;

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
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Add project in configuration');

        $this->addArgument(
            'project_name',
            InputArgument::REQUIRED,
            'Project name (e.g. example-memcached-bundle)'
        );

        $this->addArgument(
            'git_repository_url',
            InputArgument::REQUIRED,
            'Git repository url (e.g. https://github.com/example/ExampleMemcachedBundle.git)'
        );

        $this->addOption(
            'local_git_repository_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Local git repository directory',
            '%local_projects_root_dir%/%project_name%'
        );

        $this->addOption(
            'remote_git_repository_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Remote git repository directory',
            '%remote_projects_root_dir%/%project_name%'
        );

        $this->addOption(
            'git_target_branch',
            null,
            InputOption::VALUE_REQUIRED,
            'Git branch',
            'master'
        );

        $this->addOption(
            'remote_webapp_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Remote webapp directory',
            '%remote_projects_root_dir%/%project_name%'
        );

        $this->addOption(
            'local_webapp_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Local webapp directory',
            '%local_projects_root_dir%/%project_name%'
        );

        $this->addOption(
            'remote_vendor_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Remote vendor directory',
            '/home/vagrant/projects/%project_name%/vendor'
        );

        $this->addOption(
            'local_vendor_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Local vendor directory',
            '%local_vendor_root_dir%/%project_name%'
        );

        $this->addOption(
            'remote_symfony_console_path',
            null,
            InputOption::VALUE_REQUIRED,
            'Remote symfony console path',
            '%remote_vendor_root_dir%/app/console'
        );

        $this->addOption(
            'remote_phpunit_configuration_xml_path',
            null,
            InputOption::VALUE_REQUIRED,
            'Remote phpunit configuration xml path',
            '%remote_vendor_root_dir%/app/phpunit.xml.dist'
        );

        $this->addOption(
            'remote_assets_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Remote assets dir',
            '/srv/cdn'
        );

        $this->addOption(
            'local_assets_dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Local assets directory',
            '%local_cdn_root_dir%/%project_name%'
        );
    }

    /**
     * @{inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getArgument('project_name')) {
            $this->checkProjectName($input->getArgument('project_name'));
        }

        if (null !== $input->getArgument('git_repository_url')) {
            $this->checkGitRepositoryUrl($input->getArgument('git_repository_url'));
        }
    }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $data = [
            'project_name' => $input->getArgument('project_name'),
            'git_repository_url' => $input->getArgument('git_repository_url'),
            'local_git_repository_dir' => $input->getOption('local_git_repository_dir'),
            'remote_git_repository_dir' => $input->getOption('remote_git_repository_dir'),
            'git_target_branch' => $input->getOption('git_target_branch'),
            'remote_webapp_dir' => $input->getOption('remote_webapp_dir'),
            'local_webapp_dir' => $input->getOption('local_webapp_dir'),
            'remote_vendor_dir' => $input->getOption('remote_vendor_dir'),
            'local_vendor_dir' => $input->getOption('local_vendor_dir'),
            'remote_symfony_console_path' => $input->getOption('remote_symfony_console_path'),
            'remote_phpunit_configuration_xml_path' => $input->getOption('remote_phpunit_configuration_xml_path'),
            'remote_assets_dir' => $input->getOption('remote_assets_dir'),
            'local_assets_dir' => $input->getOption('local_assets_dir'),
        ];

        $helper = $this->getHelper('question');

        if (empty($data['project_name'])) {
            $question = new Question('Please enter the name of the project: ', '');
            $question->setValidator(function ($answer) {
                $this->checkProjectName($answer);

                return $answer;
            });

            $data['project_name'] = $helper->ask($input, $output, $question);
        }

        if (empty($data['git_repository_url'])) {
            $question = new Question('Please enter the url of the git repository: ', '');
            $question->setValidator(function ($answer) {
                $this->checkGitRepositoryUrl($answer);

                return $answer;
            });

            $data['git_repository_url'] = $helper->ask($input, $output, $question);
        }

        foreach ($data as $propertyPath => $propertyValue) {
            if (!empty($propertyValue)) {
                continue;
            }

            if ($propertyPath == 'project_name' || $propertyPath == 'git_repository_url') {
                continue;
            }

            $question = new Question(sprintf('Please enter the %s: ', $propertyPath), '');
            $data[$propertyPath] = $helper->ask($input, $output, $question);
        }

        $repository = $this->getProjectConfigurationRepository();
        $repository->add($data);

        $output->writeln('Add project configuration: ');

        foreach ($repository->find($data['project_name'])->toArray() as $propertyPath => $propertyValue) {
            $output->writeln(sprintf(
                '<comment>%s: <info>%s</info></comment>',
                $propertyPath,
                $propertyValue
            ));
        }
    }

    protected function checkProjectName($name)
    {
        if ($this->getProjectConfigurationRepository()->find($name)) {
            throw new \InvalidArgumentException(
                'The name of the project already exists'
            );
        }
    }

    protected function checkGitRepositoryUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && 0 !== strpos($url, 'git@')) {
            throw new \InvalidArgumentException(
                sprintf('This value \'%s\' is not a valid URL.', $url)
            );
        }

        if ($this->getProjectConfigurationRepository()->findBy(['git_repository_url' => $url])) {
            throw new \InvalidArgumentException(
                sprintf('This value \'%s\' already exists.', $url)
            );
        }
    }
}
