#!/usr/bin/env bash

set -euxo pipefail

# This script is used to build the binary files for SolidInvoice.
#
# Usage:
#   ./build_binary.sh [VERSION] [--local] [--skip-dist]
#
# Arguments:
#   VERSION     - Version number for the binary (default: current branch name)
#   --local     - Use local filesystem for dist build (ALWAYS rebuilds dist with latest changes)
#   --skip-dist - Skip building dist archive (assumes it already exists)
#
# Examples:
#   ./build_binary.sh                    # Dev build of current branch
#   ./build_binary.sh --local            # Dev build using local files (rebuilds dist)
#   ./build_binary.sh v2.3.0             # Release build
#   ./build_binary.sh --skip-dist        # Rebuild binary only (reuse existing dist)

show_help() {
    cat << 'EOF'
SolidInvoice Binary Builder

Builds standalone FrankenPHP binaries for SolidInvoice.
Automatically builds the distribution archive if needed.

USAGE:
    ./build_binary.sh [VERSION] [--local] [--skip-dist] [--help]

ARGUMENTS:
    VERSION         Version number for the binary (default: current branch name)

OPTIONS:
    --local         Use local filesystem for dist build instead of cloning
                    Recommended for development and testing
                    ALWAYS rebuilds dist to include latest local changes
    --skip-dist     Skip building dist archive (assumes it already exists)
                    Useful for rebuilding binary with existing dist
                    Cannot be used with --local
    --help          Show this help message

EXAMPLES:
    # Development builds (recommended for local testing)
    ./build_binary.sh --local                    # Auto-builds dist + binary from local files
    ./build_binary.sh feature-auth --local       # Build specific branch locally

    # Using existing dist archive
    ./build_binary.sh --skip-dist                # Rebuild binary only
    ./build_binary.sh v2.3.0 --skip-dist         # Use existing v2.3.0 dist

    # Production builds
    ./build_binary.sh v2.3.0                     # Clone from GitHub, build everything
    RELEASE=1 ./build_binary.sh v2.3.0           # Build and upload to GitHub

ENVIRONMENT:
    RELEASE=1           Upload binary to GitHub releases (requires gh CLI)
    PHP_VERSION=8.4     PHP version to build (default: 8.4)
    DEBUG_SYMBOLS=1     Include debug symbols (larger binary)
    CLEAN=1             Clean build cache before building

OUTPUT:
    build/dist/SolidInvoice-{VERSION}.tar.gz     (if not --skip-dist)
    build/dist/SolidInvoice-{VERSION}.zip        (if not --skip-dist)
    frankenphp/dist/solidinvoice-{os}-{arch}     (binary)

WORKFLOW:
    1. Checks if dist archive exists (unless --local)
    2. If --local: ALWAYS rebuilds dist with latest changes
    3. If --skip-dist: Uses existing dist (must exist)
    4. Otherwise: Builds dist only if it doesn't exist
    5. Copies dist archive to frankenphp/app.tar.gz
    6. Builds static binary via frankenphp/build-solidinvoice.sh
    7. Optionally uploads to GitHub releases

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

USE_LOCAL=0
SKIP_DIST=0

# Parse arguments
for arg in "$@"; do
    if [ "$arg" = "--local" ]; then
        USE_LOCAL=1
    elif [ "$arg" = "--skip-dist" ]; then
        SKIP_DIST=1
    fi
done

# Get current branch if in a git repo
CURRENT_BRANCH=""
if git rev-parse --git-dir > /dev/null 2>&1; then
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
fi

# Set defaults
SOLIDINVOICE_VERSION=${1:-$CURRENT_BRANCH}

# Remove flags from version
SOLIDINVOICE_VERSION=${SOLIDINVOICE_VERSION//--local/}
SOLIDINVOICE_VERSION=${SOLIDINVOICE_VERSION//--skip-dist/}

# Trim whitespace
SOLIDINVOICE_VERSION=$(echo "$SOLIDINVOICE_VERSION" | xargs)

# Interactive prompt if still empty
if [ -z "$SOLIDINVOICE_VERSION" ]; then
    if [ $USE_LOCAL -eq 1 ]; then
        # For local builds, use current commit SHA as version
        SOLIDINVOICE_VERSION=$(git rev-parse --short HEAD)
        echo "Using local build with version: $SOLIDINVOICE_VERSION"
    else
        echo "Enter version number: "
        read -r version
        SOLIDINVOICE_VERSION=${version}
    fi
fi

ROOT_DIR=$( dirname "$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)")
BUILD_DIR="$ROOT_DIR/build"
DIST_DIR="$BUILD_DIR/dist/"

# Check if we need to build the dist archive
NEED_BUILD_DIST=0

if [ $SKIP_DIST -eq 1 ]; then
    # User explicitly wants to skip dist build
    if [ ! -f "${DIST_DIR}"/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz ]; then
        echo "Error: Dist archive does not exist and --skip-dist was specified."
        echo "Expected: ${DIST_DIR}/SolidInvoice-${SOLIDINVOICE_VERSION}.tar.gz"
        exit 1
    fi
    echo "Skipping dist build (--skip-dist specified)"
    echo "Using existing dist archive: ${DIST_DIR}/SolidInvoice-${SOLIDINVOICE_VERSION}.tar.gz"
elif [ $USE_LOCAL -eq 1 ]; then
    # For --local, ALWAYS rebuild to pick up latest changes
    NEED_BUILD_DIST=1
    if [ -f "${DIST_DIR}"/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz ]; then
        echo "Removing existing dist archive to rebuild with latest local changes..."
        rm "${DIST_DIR}"/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz
        rm -f "${DIST_DIR}"/SolidInvoice-"$SOLIDINVOICE_VERSION".zip
    fi
    echo "Building dist archive from local changes..."
elif [ ! -f "${DIST_DIR}"/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz ]; then
    # Dist doesn't exist, need to build it
    NEED_BUILD_DIST=1
    echo "Dist archive not found. Building it automatically..."
else
    # Dist exists and not using --local, reuse it
    echo "Using existing dist archive: ${DIST_DIR}/SolidInvoice-${SOLIDINVOICE_VERSION}.tar.gz"
fi

# Build dist if needed
if [ $NEED_BUILD_DIST -eq 1 ]; then
    if [ $USE_LOCAL -eq 1 ]; then
        "${ROOT_DIR}/scripts/build_dist.sh" "$SOLIDINVOICE_VERSION" "$SOLIDINVOICE_VERSION" --local
    else
        "${ROOT_DIR}/scripts/build_dist.sh" "$SOLIDINVOICE_VERSION" "$SOLIDINVOICE_VERSION"
    fi

    # Verify the build was successful
    if [ ! -f "${DIST_DIR}"/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz ]; then
        echo "Error: Dist build failed. Archive was not created."
        exit 1
    fi

    echo "Dist archive built successfully."
fi

cd "${ROOT_DIR}/frankenphp"


cp "${DIST_DIR}"/SolidInvoice-"$SOLIDINVOICE_VERSION".tar.gz ./app.tar.gz

# Use the SolidInvoice wrapper script which calls build-static.sh with proper config
export SOLIDINVOICE_VERSION
./build-solidinvoice.sh
