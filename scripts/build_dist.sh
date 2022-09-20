#!/usr/bin/env bash

set -euo pipefail

# This script is used to build the distribution archives for SolidInvoice.

export SOLIDINVOICE_ENV=prod
export SOLIDINVOICE_DEBUG=0

NODE_ENVIRONMENT=production
REPO=https://github.com/SolidInvoice/SolidInvoice-Test.git
BRANCH=${1:-}
VERSION=${2:-}

if [ -z $BRANCH ]
then
    echo "Enter branch or tag name to checkout: "
    read branch

    BRANCH=${branch}
fi

if [ -z $VERSION ]
then
    echo "Enter version number: "
    read version

    VERSION=${version}
fi

ROOT_DIR=$( dirname $(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd))
BUILD_DIR="$ROOT_DIR/build"
DIST_DIR="$BUILD_DIR/dist/"

function semverParse() {
    local RE='[^0-9]*\([0-9]*\)[.]\([0-9]*\)[.]\([0-9]*\)\([0-9A-Za-z-]*\)'
    #MAJOR
    eval $2=`echo $1 | sed -e "s#$RE#\1#"`
    #MINOR
    eval $3=`echo $1 | sed -e "s#$RE#\2#"`
    #MINOR
    eval $4=`echo $1 | sed -e "s#$RE#\3#"`
    #SPECIAL
    eval $5=`echo $1 | sed -e "s#$RE#\4#"`
}

function generateRelease() {
    rm -Rf ../build/*

    mkdir -p "${BUILD_DIR}"
    mkdir -p "$DIST_DIR"

    cd "${BUILD_DIR}"

    git clone "${REPO}" "./SolidInvoice"
    cd "./SolidInvoice"
    git checkout "${BRANCH}"

    composer config --no-plugins allow-plugins.symfony/flex true
    composer install -o -n --no-dev -vvv
    yarn --pure-lockfile
    php bin/console assets:install
    yarn build
    php bin/console fos:js-routing:dump --callback=define
    php bin/console bazinga:js-translation:dump --merge-domains public
    rm -Rf node_modules
    chmod -R 0777 var
    rm -Rf .env
    echo "SOLIDINVOICE_ENV=$SOLIDINVOICE_ENV" >> .env
    echo "SOLIDINVOICE_DEBUG=$SOLIDINVOICE_DEBUG" >> .env

    zip -r SolidInvoice-$VERSION_MAJOR.$VERSION_MINOR.$VERSION_PATCH$VERSION_SPECIAL.zip ./
    mv SolidInvoice-$VERSION_MAJOR.$VERSION_MINOR.$VERSION_PATCH$VERSION_SPECIAL.zip "${DIST_DIR}"

    tar -zcvf SolidInvoice-$VERSION_MAJOR.$VERSION_MINOR.$VERSION_PATCH$VERSION_SPECIAL.tar.gz ./
    mv SolidInvoice-$VERSION_MAJOR.$VERSION_MINOR.$VERSION_PATCH$VERSION_SPECIAL.tar.gz "${DIST_DIR}"

    cd ../ && rm -Rf "./SolidInvoice"
}

semverParse $VERSION VERSION_MAJOR VERSION_MINOR VERSION_PATCH VERSION_SPECIAL

generateRelease
