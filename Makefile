install:
	@ echo '[curl] Getting Composer, the PHP dependency manager'
	@ which composer || curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin
	@ echo '[composer] Downloading the dependencies'
	@ composer install --no-dev --optimize-autoloader

install-php-tools:
	@ composer global require "mmoreram/php-formatter"
	@ composer global require "friendsofphp/php-cs-fixer"
	@ composer global require "halleck45/phpmetrics"

# Build phar
build-phar:
	@ box build

cs-fix:
	@ php-formatter formatter:use:sort src
	@ php-cs-fixer fix

metrics:
	@ phpmetrics --level=0 --report-html=build/metrics.html src/ && (which open && open build/metrics.html) || (which xdg-open && xdg-open build/metrics.html)

# Publish new release. Usage:
#   make tag VERSION=(major|minor|patch)
# You need to install https://github.com/flazz/semver/ before with command sudo gem install semver
# Source: http://blog.lepine.pro/semver-git-tags/
tag:
	@ semver inc $(VERSION)
	@ echo "New release: `semver tag`"

# Tag git with last release
git_tag:
	@ sh bin/releaser
