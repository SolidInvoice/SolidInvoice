#!/usr/bin/env bash

set -euo pipefail

# This script is used to build the distribution archives for SolidInvoice.

export SOLIDINVOICE_ENV=prod
export SOLIDINVOICE_DEBUG=0
export NODE_ENVIRONMENT=production

REPO=https://github.com/SolidInvoice/SolidInvoice.git
BRANCH=${1:-}
VERSION=${2:-}

if [ -z "$BRANCH" ]
then
    echo "Enter branch or tag name to checkout: "
    read -r branch

    BRANCH=${branch}
fi

if [ -z "$VERSION" ]
then
    echo "Enter version number: "
    read -r version

    VERSION=${version}
fi

ROOT_DIR=$( dirname "$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)")
BUILD_DIR="$ROOT_DIR/build"
DIST_DIR="$BUILD_DIR/dist/"

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
    yarn build
    rm -Rf node_modules .env .git
    chmod -R 0777 var

    echo "SOLIDINVOICE_ENV=$SOLIDINVOICE_ENV" >> .env
    echo "SOLIDINVOICE_DEBUG=$SOLIDINVOICE_DEBUG" >> .env

    zip -r SolidInvoice-"$VERSION".zip ./
    mv SolidInvoice-"$VERSION".zip "${DIST_DIR}"

    tar -zcvf SolidInvoice-"$VERSION".tar.gz ./
    mv SolidInvoice-"$VERSION".tar.gz "${DIST_DIR}"

    cd ../ && rm -Rf "./SolidInvoice"
}

generateRelease
