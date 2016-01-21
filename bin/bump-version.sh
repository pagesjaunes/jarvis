#!/bin/bash
# inspiration from https://github.com/MattKetmo/cliph/blob/master/bump-version.sh
set -e

if [ $# -ne 1 ]; then
  echo "Usage: `basename $0` <tag>"
  exit 65
fi


# CHECK MASTER BRANCH
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [[ "$CURRENT_BRANCH" != "master" ]]; then
  echo "You have to be on master branch currently on $CURRENT_BRANCH . Aborting"
  exit 65
fi

# CHECK FORMAT OF THE TAG
php -r "if(preg_match('/^\d+\.\d+\.\d+(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?\$/',\$argv[1])) exit(0) ;else{ echo 'format of version tag is not invalid' . PHP_EOL ; exit(1);}" $1

# CHECK box COMMAND
command -v box >/dev/null 2>&1 || { echo "Error : Command box is not installed on the system"; echo "See : https://github.com/box-project/box2 "; echo  "Exiting..." >&2; exit 65; }

# CHECK THAT WE CAN CHANGE BRANCH
git checkout gh-pages
git checkout --quiet master

TAG=$1

#
# Tag & build master branch
#
git checkout master
git flow release start ${TAG}
git flow release finish ${TAG}

rm -fr vendor
composer install

mkdir -p build
ulimit -Sn 4096
box build

SHA1=$(openssl sha1 build/jarvis.phar | awk '{print $2}')

echo ${SHA1}

#
# Copy executable file into GH pages
#
git checkout gh-pages

mkdir -p releases/download/${TAG}
cp build/jarvis.phar releases/download/${TAG}/jarvis.phar
git add releases/download/${TAG}/jarvis.phar

#
# Update manifest
#
JSON='"name":"jarvis.phar"'
JSON="${JSON},\"sha1\":\"${SHA1}\""
JSON="${JSON},\"url\":\"http://pagesjaunes.github.io/jarvis/releases/download/${TAG}/jarvis.phar\""
JSON="${JSON},\"version\":\"${TAG}\""

echo '[{'${JSON}'}]' > build/manifest.json.tmp

jq -s add manifest.json build/manifest.json.tmp > manifest.json.tmp
mv manifest.json.tmp manifest.json
rm build/manifest.json.tmp
git add manifest.json

git commit -m "Bump version ${TAG}"

#
# Go back to master
#
git checkout master

echo "New version created. Now you should run:"
echo "git push origin master"
echo "git push origin gh-pages"
echo "git push --tags"
