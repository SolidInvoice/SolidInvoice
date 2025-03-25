#!/usr/bin/env bash

set -euxo pipefail

# This script is used to build the distribution archives for SolidInvoice.

export SOLIDINVOICE_ENV=prod
export SOLIDINVOICE_DEBUG=0
export NODE_ENVIRONMENT=production

REPO=https://github.com/pierredup/solidinvoice.git
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


rm -Rf build/*

mkdir -p "${BUILD_DIR}"
mkdir -p "$DIST_DIR"

cd "${BUILD_DIR}"

git clone "${REPO}" "./SolidInvoice"
cd "./SolidInvoice"
git checkout "${BRANCH}"

composer config --no-plugins allow-plugins.symfony/flex true
composer install -o -n --no-dev -a
#composer require runtime/frankenphp-symfony
bun install
bun run build
rm -Rf node_modules .env .git
chmod -R 0777 var

echo "SOLIDINVOICE_ENV=$SOLIDINVOICE_ENV" >> .env
echo "SOLIDINVOICE_DEBUG=$SOLIDINVOICE_DEBUG" >> .env

chmod a+w config

zip -qr "${DIST_DIR}/SolidInvoice-$VERSION".zip ./
tar -czf "${DIST_DIR}/SolidInvoice-$VERSION".tar.gz ./

if [ -n "${RELEASE}" ]; then
	gh release upload "${SOLIDINVOICE_VERSION}" ${DIST_DIR}/SolidInvoice-$VERSION".zip --repo pierredup/solidinvoice --clobber
	gh release upload "${SOLIDINVOICE_VERSION}" "${DIST_DIR}/SolidInvoice-$VERSION".tar.gz --repo pierredup/solidinvoice --clobber
fi

cd ../ && rm -Rf "./SolidInvoice"
