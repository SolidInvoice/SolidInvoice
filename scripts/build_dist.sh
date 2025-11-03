#!/usr/bin/env bash

set -euxo pipefail

# This script is used to build the distribution archives for SolidInvoice.

export SOLIDINVOICE_ENV=prod
export SOLIDINVOICE_DEBUG=0
export NODE_ENVIRONMENT=production

REPO=https://github.com/solidinvoice/solidinvoice.git
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

git clone --branch "${BRANCH}" --depth 1 "${REPO}" "./SolidInvoice"
cd "./SolidInvoice"

composer config --no-plugins allow-plugins.symfony/flex true
composer install -o -n --no-dev -a --ignore-platform-reqs # Platform requirements can be ignored since it's not needed on the build server
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

if [ "${RELEASE:-}" = "1" ]; then
	gh release upload "${VERSION}" "${DIST_DIR}"/SolidInvoice-"${VERSION}".zip --repo solidinvoice/solidinvoice --clobber
	gh release upload "${VERSION}" "${DIST_DIR}"/SolidInvoice-"${VERSION}".tar.gz --repo solidinvoice/solidinvoice --clobber
fi

cd ../ && rm -Rf "./SolidInvoice"
