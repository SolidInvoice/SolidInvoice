#!/usr/bin/env bash

set -euxo pipefail

# This script is used to build the distribution archives for SolidInvoice.
#
# Usage:
#   ./build_dist.sh [BRANCH] [VERSION] [--local]
#
# Arguments:
#   BRANCH   - Branch or tag to checkout (default: current branch)
#   VERSION  - Version number for the archive (default: branch name or commit SHA)
#   --local  - Use local filesystem instead of cloning (recommended for dev/testing)
#
# Examples:
#   ./build_dist.sh                           # Dev build of current branch
#   ./build_dist.sh --local                   # Dev build using local files
#   ./build_dist.sh main v2.3.0               # Release build from remote
#   ./build_dist.sh feature-branch --local    # Dev build of specific branch locally

show_help() {
    cat << 'EOF'
SolidInvoice Distribution Builder

Builds distribution archives (.tar.gz and .zip) for SolidInvoice.

USAGE:
    ./build_dist.sh [BRANCH] [VERSION] [--local] [--help]

ARGUMENTS:
    BRANCH      Branch or tag to checkout (default: current branch)
    VERSION     Version number for archive filename (default: branch name or commit SHA)

OPTIONS:
    --local     Use local filesystem instead of cloning from GitHub
                Recommended for development and testing
    --help      Show this help message

EXAMPLES:
    # Development builds (using local files)
    ./build_dist.sh --local                      # Current branch, auto version
    ./build_dist.sh feature-auth --local         # Specific branch locally

    # Production builds (clone from GitHub)
    ./build_dist.sh 2.3.x v2.3.0                 # Clone and build from remote
    RELEASE=1 ./build_dist.sh 2.3.x v2.3.0       # Build and upload to GitHub

ENVIRONMENT:
    RELEASE=1   Upload archives to GitHub releases (requires gh CLI)

OUTPUT:
    build/dist/SolidInvoice-{VERSION}.tar.gz
    build/dist/SolidInvoice-{VERSION}.zip

For more details, see scripts/BUILD_GUIDE.md
EOF
    exit 0
}

# Check for help flag
for arg in "$@"; do
    if [ "$arg" = "--help" ] || [ "$arg" = "-h" ]; then
        show_help
    fi
done

set -euxo pipefail

export SOLIDINVOICE_ENV=prod
export SOLIDINVOICE_DEBUG=0
export NODE_ENVIRONMENT=production

REPO=https://github.com/solidinvoice/solidinvoice.git
USE_LOCAL=0

# Parse arguments and filter out --local
ARGS=()
for arg in "$@"; do
    if [ "$arg" = "--local" ]; then
        USE_LOCAL=1
    else
        ARGS+=("$arg")
    fi
done

# Get current branch if in a git repo
CURRENT_BRANCH=""
if git rev-parse --git-dir > /dev/null 2>&1; then
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
fi

# Set defaults using filtered arguments
BRANCH=${ARGS[0]:-$CURRENT_BRANCH}
VERSION=${ARGS[1]:-$BRANCH}

# Interactive prompts if still empty
if [ -z "$BRANCH" ]; then
    echo "Enter branch or tag name to checkout: "
    read -r branch
    BRANCH=${branch}
fi

if [ -z "$VERSION" ]; then
    if [ $USE_LOCAL -eq 1 ]; then
        # For local builds, use current commit SHA as version
        VERSION=$(git rev-parse --short HEAD)
        echo "Using local build with version: $VERSION"
    else
        echo "Enter version number: "
        read -r version
        VERSION=${version}
    fi
fi

ROOT_DIR=$( dirname "$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)")
BUILD_DIR="$ROOT_DIR/build"
DIST_DIR="$BUILD_DIR/dist/"

rm -Rf build/*

mkdir -p "${BUILD_DIR}"
mkdir -p "$DIST_DIR"

cd "${BUILD_DIR}"

if [ $USE_LOCAL -eq 1 ]; then
    echo "Using local filesystem for build..."

    # Check for rsync (required for local builds)
    if ! command -v rsync >/dev/null 2>&1; then
        echo "Error: rsync is required for local builds but is not installed."
        echo ""
        echo "Please install rsync:"
        echo "  - macOS:   brew install rsync"
        echo "  - Ubuntu:  sudo apt-get install rsync"
        echo "  - Fedora:  sudo dnf install rsync"
        echo "  - Alpine:  apk add rsync"
        echo ""
        exit 1
    fi

    # Copy current repository state to build directory
    rsync -a --exclude='build/' \
             --exclude='node_modules/' \
             --exclude='vendor/' \
             --exclude='var/cache/' \
             --exclude='var/log/' \
             --exclude='.git/' \
             --exclude='frankenphp' \
             "${ROOT_DIR}/" "./SolidInvoice/"
    cd "./SolidInvoice"
else
    echo "Cloning from remote repository..."
    git clone --branch "${BRANCH}" --depth 1 "${REPO}" "./SolidInvoice"
    cd "./SolidInvoice"
fi

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
