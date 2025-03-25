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
    echo "Build file not found. Please run build_dist.sh first."
    exit 1
fi

cd "${ROOT_DIR}/frankenphp"

cp "${DIST_DIR}/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz" ./app.tar.gz
gunzip app.tar.gz

targets=(
  "linux/amd64"
  "linux/arm64"
  "linux/386"
  "darwin/amd64"
  "darwin/arm64"
  "windows/amd64"
  "windows/arm64"
)

for t in "${targets[@]}"; do
  IFS="/" read -r GOOS GOARCH <<< "$t"
  [ "$GOOS" == "windows" ] && BIN_NAME="myapp.exe"

  GOOS="$GOOS" GOARCH="$GOARCH" PHP_VERSION=8.4 ./build-static.sh
done

rm app.tar
