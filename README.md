Jarvis
================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0d176b6e-0050-4288-b24a-49951e8b85ba/big.png)](https://insight.sensiolabs.com/projects/0d176b6e-0050-4288-b24a-49951e8b85ba)

The Ultimate tool to manage php application in virtual machine.

Jarvis is designed to assist you in developping Symfony application, by defining common tasks you run on your remote servers.

Jarvis is meant to be your Swiss Army knife, so it features a modular design with multiple sub-commands to keep the codebase manageable and future-friendly.

What is it?
-----------

The Jarvis simplifies the developing Symfony Application with vagrant virtual machine.

Out of the box, the application can do many great things:
**composer:**

* composer:self-update
* composer:show

**core:**

* core:container:cache-clear

**editor:**

* editor:config:install

**project:**

* project:assets:build
* project:assets:watch

* project:composer:graph-dependencies
* project:composer:graph-dependencies-pagesjaunes
* project:composer:install
* project:composer:update
* project:composer:validate
* project:config:add
* project:config:show
* project:cs:php
* project:editor:open
* project:git:clone
* project:git:hooks:install
* project:git:log
* project:git:mergetool
* project:git:pre-commit
* project:git:status
* project:git:update
* project:lint:all
* project:lint:php
* project:lint:scss
* project:lint:twig
* project:lint:yaml
* project:php:doc
* project:php:metrics
* project:symfony:cache:clear
* project:symfony:debug:container
* project:symfony:debug:event-dispatcher
* project:symfony:debug:router
* project:symfony:debug:translation
* project:symfony:debug:twig
* project:symfony:monitor:health
* project:tests:all
* project:tests:integration
* project:tests:unit

**vagrant:**

* vagrant:build
* vagrant:provision
* vagrant:restart
* vagrant:ssh
* vagrant:start
* vagrant:status
* vagrant:stop

**vm:**

* vm:php:extension:disable
* vm:php:extension:enable
* vm:service
* vm:service:nginx:restart
* vm:service:php_fpm:restart
* vm:service:varnish:restart

How do I get started?
---------------------

You can use Jarvis in one of three ways:

### As a phar (recommended)

You may download a ready-to-use version of Box as a Phar:

```bash
$ curl -LSs http://pagesjaunes.github.io/jarvis/installer | php
```

The command will check your PHP settings, warn you of any issues, and the download it to the current directory. From there, you may place it anywhere that will make it easier for you to access (such as /usr/local/bin) and chmod it to 755. You can even rename it to just jarvis to avoid having to type the .phar extension every time.

```bash
$ jarvis —version
```

Whenever a new version of the application is released, you can simply run the update command to get the latest version:

```bash
$ jarvis self-update
```

### As a global composer install

This is probably the best way when you have other tools like phpunit and other tools installed in this way:

```bash
$ composer global require pagesjaunes/jarvis-ci --prefer-dist
```

Make sure to place the **~/.composer/vendor/bin** directory in your PATH so the **jarvis** executable is found when you run the **jarvis** command in your terminal.

### As a composer dependency

You may also install Jarvis as a dependency for your Composer managed project:

```bash
$ composer require --dev pagesjaunes/jarvis
```

(or)

```json
{
    "require-dev": {
        "pagesjaunes/jarvis": "0.1"
    }
}
```

Once you have installed the application, you can run the help command to get detailed information about all of the available commands. This should be your go-to place for information about how to use Jarvis. You may also find additional useful information on the wiki. If you happen to come across any information that could prove to be useful to others, the wiki is open for you to contribute.

```bash
$ jarvis help
```

Managing a Symfony project
--------------------------

To get started, you may want to check out the example application that is ready to be manage by Jarvis. How your project is structured is entirely up to you. All that Jarvis requires is that you have a file called jarvis.yml at the root of your project directory. You can find a complete and detailed list of configuration settings available by seeing the help information for the build command:

```bash
$ jarvis help project:add
```

Working Directory
------------------

If specified option **--working-dir (-d)**, use the given directory as working directory.

Example working directory:
```bash
$ tree -L 1
.
├── README.md
├── assets
├── bin
├── build
├── config
├── jarvis (facultative for extending)
├── jarvis.yml
├── projects
├── provisioning (facultative)
├── vagrant
├── var
└── vendor
```

Example content file *jarvis.yml*:

```yaml
imports:
    - { resource: jarvis/config/services.xml }

parameters:
    app.name: pj-jarvis

    vagrant_directory: "%working_dir%/vagrant"
    projects_config_file_path: "%working_dir%/config/projects.json"
    local_projects_root_dir: "%working_dir%/projects"

    editor_project_external_folders_config:
        - { name: "bundles", path: "%working_dir%/projects/bundles", follow_symlinks: "false" }
        - { name: "assets_common", path: "%working_dir%/assets/common", follow_symlinks: "false" }

    command.project.enabled: true
    command.editor.enabled: true
    command.project.build.enabled: true
    command.project.cs.enabled: true
    command.project.editor.enabled: true
    command.project.git.enabled: true
    command.project.php.enabled: true
    command.project.composer.enabled: true
    command.project.symfony.enabled: true
    command.project.symfony.assets.enabled: true
    command.project.test.enabled: true
    command.project.lint.enabled: true
    command.vagrant.enabled: true
    command.vm.enabled: true
    command.composer.enabled: true
```

Example content file *config/projects.json*:

```json
{
    "projects": {
        {
            "project_name": "symfony-distibution",
            "git_repository_url": "https://github.com/symfony/symfony-standard.git",
            "local_git_repository_dir": "%local_projects_root_dir%/webapps/%project_name%",
            "remote_git_repository_dir": "%remote_projects_root_dir%/webapps/%project_name%",
            "git_target_branch": "master",
            "remote_webapp_dir": "%remote_projects_root_dir%/webapps/%project_name%",
            "local_webapp_dir": "%local_projects_root_dir%/webapps/%project_name%",
            "remote_vendor_dir": "/home/vagrant/projects/%project_name%/vendor",
            "local_vendor_dir": "%local_vendor_root_dir%/%project_name%",
            "remote_symfony_console_path": "%remote_webapp_dir%/app/console",
            "remote_phpunit_configuration_xml_path": "%remote_webapp_dir%/app/phpunit.xml.dist"
        },
        "sylius": {
            "project_name": "sylius",
            "git_repository_url": "https://github.com/Sylius/Sylius.git",
            "local_git_repository_dir": "%local_projects_root_dir%/webapps/%project_name%",
            "remote_git_repository_dir": "%remote_projects_root_dir%/webapps/%project_name%",
            "git_target_branch": "master",
            "remote_webapp_dir": "%remote_projects_root_dir%/webapps/%project_name%",
            "local_webapp_dir": "%local_projects_root_dir%/webapps/%project_name%",
            "remote_vendor_dir": "/home/vagrant/projects/%project_name%/vendor",
            "local_vendor_dir": "%local_vendor_root_dir%/%project_name%",
            "remote_symfony_console_path": "%remote_webapp_dir%/app/console",
            "remote_phpunit_configuration_xml_path": "%remote_webapp_dir%/app/phpunit.xml.dist"
        }
    }
}
```

Alias
-----

Example:

alias example-jarvis="/usr/local/bin/jarvis --working-dir=/path/to/projects/socle"

License
-------

Licenced under the [MIT](http://opensource.org/licenses/MIT) licence. See [here](https://github.com/pagesjaunes/jarvis/blob/develop/LICENSE.md)

Contributing
------------

You can contribute in one of three ways:

- File bug reports using the issue tracker.
- Answer questions or fix bugs on the issue tracker.
- Contribute new features or update the wiki.

The code contribution process is not very formal. You just need to make sure that you follow the symfony coding guidelines. Any new code contributions must be accompanied by unit tests where applicable.

Builds Statuses
---------------

Dev Branch: [![Build Status](https://travis-ci.org/pagesjaunes/jarvis.png?branch=develop)](https://travis-ci.org/pagesjaunes/jarvis)

Master Branch: [![Build Status](https://travis-ci.org/pagesjaunes/jarvis.png?branch=master)](https://travis-ci.org/pagesjaunes/jarvis)
