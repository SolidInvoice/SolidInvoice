#!/usr/bin/env bash

set -euxo pipefail

# This script is used to build the binary files for SolidInvoice.

SOLIDINVOICE_VERSION=${1:-}

if [ -z "$SOLIDINVOICE_VERSION" ]
then
    echo "Enter version number: "
    read -r version

    SOLIDINVOICE_VERSION=${version}
fi

ROOT_DIR=$( dirname "$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)")
BUILD_DIR="$ROOT_DIR/build"
DIST_DIR="$BUILD_DIR/dist/"

# Check if build file exists, exit if it doesn't

if [ ! -f "${DIST_DIR}/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz" ]; then
    "${ROOT_DIR}"/scripts/build_dist.sh "$SOLIDINVOICE_VERSION" "$SOLIDINVOICE_VERSION"
fi

cd "${ROOT_DIR}/frankenphp"

cp "${DIST_DIR}/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz" ./app.tar.gz
gunzip -f app.tar.gz

./build-static.sh

rm app.tar
