#!/usr/bin/env bash

set -euxo pipefail

# SolidInvoice Binary Builder Wrapper
#
# This script uses the upstream FrankenPHP build-static.sh without modifications
# by providing a fake "xcaddy" script that builds our custom Go application.
#
# Strategy:
# 1. Create a fake xcaddy script in bin/ that runs `go build` with our app.go
# 2. Add bin/ to PATH before calling build-static.sh
# 3. When static-php-cli calls "xcaddy build", it uses our fake script
# 4. Result: Upstream script runs unchanged, but we get our custom binary
#
# Benefits:
# - build-static.sh stays completely unmodified (easy upstream updates)
# - All env vars and CGO flags set by static-php-cli are preserved
# - Final binary has ONLY SolidInvoice commands (no Caddy/FrankenPHP CLI)
#
# Environment variables:
#   SOLIDINVOICE_VERSION - Version for the binary (mapped to FRANKENPHP_VERSION)
#   All other FrankenPHP env vars are also supported

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# ============================================================================
# Setup Fake xcaddy Strategy
# ============================================================================

# static-php-cli overrides PATH to use its own installed xcaddy
# We need to replace their xcaddy binary with ours
# Location: dist/static-php-cli/pkgroot/{arch}-{os}/go-xcaddy/bin/xcaddy

# Detect arch and os using static-php-cli's naming convention
spc_arch="$(uname -m)"
spc_os="$(uname -s | tr '[:upper:]' '[:lower:]')"

# Map to static-php-cli's naming convention
case "${spc_arch}" in
	arm64|aarch64) spc_arch="aarch64" ;;
	x86_64|amd64) spc_arch="x86_64" ;;
esac
# static-php-cli uses "darwin" not "mac"

FAKE_XCADDY="${SCRIPT_DIR}/bin/xcaddy"
SPC_XCADDY_DIR="${SCRIPT_DIR}/dist/static-php-cli/pkgroot/${spc_arch}-${spc_os}/go-xcaddy/bin"
SPC_XCADDY="${SPC_XCADDY_DIR}/xcaddy"

if [ ! -f "${FAKE_XCADDY}" ]; then
	echo "Error: Fake xcaddy script not found at ${FAKE_XCADDY}"
	exit 1
fi

if [ ! -x "${FAKE_XCADDY}" ]; then
	echo "Error: ${FAKE_XCADDY} is not executable"
	exit 1
fi

# ============================================================================
# Version Configuration
# ============================================================================

if [ -d "${SCRIPT_DIR}/.git/" ]; then
	DEFAULT_VERSION="$(git -C "${SCRIPT_DIR}" rev-parse --abbrev-ref HEAD)"
	if [ "$DEFAULT_VERSION" = "HEAD" ]; then
		DEFAULT_VERSION="$(git -C "${SCRIPT_DIR}" rev-parse --short HEAD)"
	fi
else
	DEFAULT_VERSION="dev-$(date +%Y%m%d-%H%M%S)_FrankenPHP"
fi

if [ -n "${SOLIDINVOICE_VERSION:-}" ]; then
	export FRANKENPHP_VERSION="${SOLIDINVOICE_VERSION}-FrankenPHP"
	VERSION="${SOLIDINVOICE_VERSION}"
else
	VERSION="${DEFAULT_VERSION}"
	export SOLIDINVOICE_VERSION="${DEFAULT_VERSION}"
	export FRANKENPHP_VERSION="${DEFAULT_VERSION}"
fi

echo "========================================"
echo "Building SolidInvoice Static Binary"
echo "========================================"
echo "Version: ${VERSION}"
echo "Platform: ${spc_arch}-${spc_os}"
echo "Strategy: Replace static-php-cli's xcaddy with our custom script"
echo "Target xcaddy: ${SPC_XCADDY}"
echo ""

# ============================================================================
# PHP Configuration
# ============================================================================

if [ -z "${PHP_VERSION:-}" ]; then
	export PHP_VERSION="8.5"
fi

# SolidInvoice-specific extensions (removed problematic/unnecessary ones)

if [ -z "${PHP_EXTENSIONS:-}" ]; then
    export PHP_EXTENSIONS=$(cat ./build-static.sh | grep 'defaultExtensions="' | head -n 1 | sed 's/.*defaultExtensions="//' | sed 's/".*//' | tr ',' '\n' | grep -v -E 'ssh2|gmp|pdo_sqlsrv|memcache|memcached' | tr '\n' ',' | sed 's/,$//')
    # excimer is required for Sentry profiling (profiles_sample_rate > 0).
    # It is not in the upstream default set so we add it here.
    export PHP_EXTENSIONS="${PHP_EXTENSIONS},excimer"
fi

if [ -z "${PHP_EXTENSION_LIBS:-}" ]; then
	export PHP_EXTENSION_LIBS="libavif,nghttp2,nghttp3,ngtcp2"
fi

echo "PHP Configuration:"
echo "  Version: ${PHP_VERSION}"
echo "  Extensions: ${PHP_EXTENSIONS}"
echo "  Libraries: ${PHP_EXTENSION_LIBS}"
echo ""

# ============================================================================
# Prepare Application for Embedding
# ============================================================================

if [ ! -f "${SCRIPT_DIR}/app.tar.gz" ]; then
	echo "Error: app.tar.gz not found"
	echo "Run build_dist.sh first to create the distribution archive."
	exit 1
fi

echo "Preparing application for embedding..."
EMBED_DIR="${SCRIPT_DIR}/dist/embed-app"
rm -rf "${EMBED_DIR}"
mkdir -p "${EMBED_DIR}"
tar -xzf "${SCRIPT_DIR}/app.tar.gz" -C "${EMBED_DIR}"
export EMBED="${EMBED_DIR}"
echo "Application extracted to: ${EMBED_DIR}"
echo ""

# ============================================================================
# Run Upstream Build Script
# ============================================================================

echo "========================================"
echo "Running Upstream build-static.sh"
echo "========================================"

cd "${SCRIPT_DIR}"

# Export the source directory so our fake xcaddy knows where to build from
export SOLIDINVOICE_SOURCE_DIR="${SCRIPT_DIR}"

# Check if this is a fresh build (xcaddy not yet installed)
FRESH_BUILD=false
if [ ! -f "${SPC_XCADDY}" ]; then
	FRESH_BUILD=true
	echo "Fresh build detected (xcaddy not yet installed)"
	echo "Will do a two-phase build:"
	echo "  Phase 1: Let static-php-cli install xcaddy"
	echo "  Phase 2: Replace xcaddy and rebuild"
	echo ""
fi

# Replace xcaddy if it already exists
if [ -f "${SPC_XCADDY}" ]; then
	echo "Replacing existing xcaddy with our custom script..."
	cp "${FAKE_XCADDY}" "${SPC_XCADDY}"
	chmod +x "${SPC_XCADDY}"
	echo "✓ xcaddy replaced"
	echo ""
fi

#export SPC_OPT_BUILD_ARGS="--rebuild"

# Run the upstream build script.
# We unset RELEASE so build-static.sh does not attempt to upload to dunglas/frankenphp.
# The upload to our own repository is handled below after the binary is renamed.
SAVED_RELEASE="${RELEASE:-}"
unset RELEASE

if [ "$FRESH_BUILD" = true ]; then
	echo "Phase 1: Running build to install dependencies..."
	echo "(This phase will fail or produce a default binary - that's expected)"
	echo ""

	# Let it run and install xcaddy (might fail at build step)
	./build-static.sh || true

	# Now replace xcaddy
	if [ -f "${SPC_XCADDY}" ]; then
		echo ""
		echo "✓ xcaddy installed by static-php-cli"
		echo "Replacing with our custom script..."
		cp "${FAKE_XCADDY}" "${SPC_XCADDY}"
		chmod +x "${SPC_XCADDY}"
		echo "✓ xcaddy replaced"
		echo ""
		echo "Phase 2: Running build with custom xcaddy..."
		echo ""

		# Clean any artifacts and rebuild
		# Note: build-static.sh uses raw uname -m naming for the output binary
		arch_uname="$(uname -m)"
		os_output="$(uname -s | tr '[:upper:]' '[:lower:]')"
		[ "$os_output" = "darwin" ] && os_output="mac"
		rm -f "${SCRIPT_DIR}/dist/frankenphp-${os_output}-${arch_uname}"

		# Run again with our custom xcaddy
		./build-static.sh
	else
		echo "Error: xcaddy was not installed by static-php-cli"
		exit 1
	fi
else
	./build-static.sh
fi

export RELEASE="${SAVED_RELEASE}"

# ============================================================================
# Finalize and Test
# ============================================================================

echo ""
echo "========================================"
echo "Finalizing Build"
echo "========================================"

# build-static.sh names its output using raw uname -m (e.g. x86_64, aarch64).
# We rename to Docker's arch convention (amd64, arm64) so the Dockerfile COPY
# instruction using ${TARGETOS}-${TARGETARCH} resolves to the correct file.
arch_uname="$(uname -m)"
os_output="$(uname -s | tr '[:upper:]' '[:lower:]')"
[ "$os_output" = "darwin" ] && os_output="mac"

case "${arch_uname}" in
    x86_64)  arch_output="amd64" ;;
    aarch64) arch_output="arm64" ;;
    *)       arch_output="${arch_uname}" ;;
esac

FRANKENPHP_BIN="${SCRIPT_DIR}/dist/frankenphp-${os_output}-${arch_uname}"
SOLIDINVOICE_BIN="${SCRIPT_DIR}/dist/solidinvoice-${os_output}-${arch_output}"

if [ ! -f "${FRANKENPHP_BIN}" ]; then
	echo "Error: Expected binary not found at ${FRANKENPHP_BIN}"
	exit 1
fi

# Rename to solidinvoice
mv "${FRANKENPHP_BIN}" "${SOLIDINVOICE_BIN}"
echo "Binary renamed: ${SOLIDINVOICE_BIN}"

# Clean up embed directory
rm -rf "${EMBED_DIR}"

# Test the binary
echo ""
echo "Testing binary..."
echo ""
"${SOLIDINVOICE_BIN}" version
echo ""

echo "Available commands:"
"${SOLIDINVOICE_BIN}" --help || true

echo ""
echo "=========================================="
echo "Build Complete!"
echo "=========================================="
echo "Binary: ${SOLIDINVOICE_BIN}"
echo "Version: ${VERSION}"
echo "Size: $(du -h "${SOLIDINVOICE_BIN}" | cut -f1)"
echo ""
echo "✓ Uses upstream build-static.sh (no modifications)"
echo "✓ Includes ONLY SolidInvoice commands"
echo "✓ No Caddy/FrankenPHP CLI commands"
echo ""

# ============================================================================
# Optional: GitHub Release Upload
# ============================================================================

if [ "${RELEASE:-}" = "1" ]; then
	echo "Uploading to GitHub releases..."
	gh release upload "${VERSION}" "${SOLIDINVOICE_BIN}" --repo solidinvoice/solidinvoice --clobber
	echo "Upload complete!"
fi
