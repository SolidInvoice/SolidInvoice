#!/bin/sh
# SolidInvoice Universal Installer
#
# Usage:
#   curl -fsSL https://raw.githubusercontent.com/SolidInvoice/SolidInvoice/3.0.x/packaging/install.sh | sh
#
# Options (via environment variables):
#   VERSION          - Specific version to install (default: latest)
#   INSTALL_DIR      - Installation directory (default: /usr/local/bin)
#   INSTALL_SERVICE  - Install systemd service (default: auto-detect)
#
# Examples:
#   curl -fsSL ... | sh                                    # Install latest
#   curl -fsSL ... | VERSION=3.0.0 sh                     # Install specific version
#   curl -fsSL ... | INSTALL_DIR=$HOME/.local/bin sh       # Install to custom dir
#   curl -fsSL ... | INSTALL_SERVICE=1 sh                  # Also install systemd service

set -e

GITHUB_REPO="SolidInvoice/SolidInvoice"
BINARY_NAME="solidinvoice"
INSTALL_DIR="${INSTALL_DIR:-/usr/local/bin}"

# Colors (only if stdout is a terminal)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    NC='\033[0m'
else
    RED=''
    GREEN=''
    YELLOW=''
    BLUE=''
    NC=''
fi

info() {
    printf "${BLUE}==>${NC} %s\n" "$1"
}

success() {
    printf "${GREEN}==>${NC} %s\n" "$1"
}

warn() {
    printf "${YELLOW}WARNING:${NC} %s\n" "$1"
}

error() {
    printf "${RED}ERROR:${NC} %s\n" "$1" >&2
    exit 1
}

detect_os() {
    OS="$(uname -s | tr '[:upper:]' '[:lower:]')"
    case "$OS" in
        linux)   OS="linux" ;;
        darwin)  OS="mac" ;;
        freebsd) error "FreeBSD is not currently supported. See https://github.com/SolidInvoice/SolidInvoice/issues for tracking." ;;
        *)       error "Unsupported operating system: $OS" ;;
    esac
}

detect_arch() {
    ARCH="$(uname -m)"
    case "$ARCH" in
        x86_64|amd64)   ARCH="amd64" ;;
        aarch64|arm64)  ARCH="arm64" ;;
        *)              error "Unsupported architecture: $ARCH" ;;
    esac
}

detect_downloader() {
    if command -v curl >/dev/null 2>&1; then
        DOWNLOADER="curl"
    elif command -v wget >/dev/null 2>&1; then
        DOWNLOADER="wget"
    else
        error "Either 'curl' or 'wget' is required for installation."
    fi
}

download() {
    url="$1"
    output="$2"

    if [ "$DOWNLOADER" = "curl" ]; then
        curl -fsSL -o "$output" "$url"
    else
        wget -q -O "$output" "$url"
    fi
}

download_to_stdout() {
    url="$1"

    if [ "$DOWNLOADER" = "curl" ]; then
        curl -fsSL "$url"
    else
        wget -q -O - "$url"
    fi
}

get_latest_version() {
    info "Fetching latest version..."
    VERSION=$(download_to_stdout "https://api.github.com/repos/${GITHUB_REPO}/releases/latest" \
        | grep '"tag_name"' \
        | head -1 \
        | sed 's/.*"tag_name": *"//;s/".*//')

    if [ -z "$VERSION" ]; then
        error "Failed to determine latest version. Please specify VERSION manually."
    fi
}

check_existing() {
    if command -v "$BINARY_NAME" >/dev/null 2>&1; then
        existing_version=$("$BINARY_NAME" version 2>/dev/null || echo "unknown")
        warn "SolidInvoice is already installed (version: ${existing_version})"
        info "Upgrading to version ${VERSION}..."
    fi
}

needs_sudo() {
    # Check if we need sudo to write to the install directory
    if [ -w "$INSTALL_DIR" ]; then
        SUDO=""
    elif command -v sudo >/dev/null 2>&1; then
        SUDO="sudo"
    elif command -v doas >/dev/null 2>&1; then
        SUDO="doas"
    else
        error "Cannot write to ${INSTALL_DIR} and neither 'sudo' nor 'doas' is available."
    fi
}

install_binary() {
    BINARY_URL="https://github.com/${GITHUB_REPO}/releases/download/${VERSION}/${BINARY_NAME}-${OS}-${ARCH}"

    info "Downloading SolidInvoice ${VERSION} for ${OS}/${ARCH}..."

    tmpdir=$(mktemp -d)
    trap 'rm -rf "$tmpdir"' EXIT

    download "$BINARY_URL" "${tmpdir}/${BINARY_NAME}"

    if [ ! -f "${tmpdir}/${BINARY_NAME}" ] || [ ! -s "${tmpdir}/${BINARY_NAME}" ]; then
        error "Download failed. Binary not found for ${OS}/${ARCH}."
    fi

    chmod +x "${tmpdir}/${BINARY_NAME}"

    # Verify the binary runs
    if ! "${tmpdir}/${BINARY_NAME}" version >/dev/null 2>&1; then
        error "Downloaded binary failed verification. It may be incompatible with your system."
    fi

    info "Installing to ${INSTALL_DIR}/${BINARY_NAME}..."

    # Ensure install directory exists
    if [ ! -d "$INSTALL_DIR" ]; then
        $SUDO mkdir -p "$INSTALL_DIR"
    fi

    $SUDO mv "${tmpdir}/${BINARY_NAME}" "${INSTALL_DIR}/${BINARY_NAME}"

    success "SolidInvoice ${VERSION} installed to ${INSTALL_DIR}/${BINARY_NAME}"
}

install_systemd_service() {
    if [ "$OS" != "linux" ]; then
        return
    fi

    if ! command -v systemctl >/dev/null 2>&1; then
        return
    fi

    # Auto-detect or respect INSTALL_SERVICE
    if [ "${INSTALL_SERVICE:-}" = "0" ]; then
        return
    fi

    if [ "${INSTALL_SERVICE:-}" != "1" ]; then
        info "Skipping systemd service installation (set INSTALL_SERVICE=1 to install)"
        return
    fi

    info "Installing systemd service..."

    SERVICE_URL="https://raw.githubusercontent.com/${GITHUB_REPO}/${VERSION}/packaging/systemd/solidinvoice.service"
    ENV_URL="https://raw.githubusercontent.com/${GITHUB_REPO}/${VERSION}/packaging/systemd/solidinvoice.env"

    tmpdir_svc=$(mktemp -d)
    trap 'rm -rf "$tmpdir_svc"' EXIT

    download "$SERVICE_URL" "${tmpdir_svc}/solidinvoice.service"
    download "$ENV_URL" "${tmpdir_svc}/solidinvoice.env"

    $SUDO cp "${tmpdir_svc}/solidinvoice.service" /usr/lib/systemd/system/solidinvoice.service
    $SUDO chmod 0644 /usr/lib/systemd/system/solidinvoice.service

    $SUDO mkdir -p /etc/solidinvoice
    if [ ! -f /etc/solidinvoice/solidinvoice.env ]; then
        $SUDO cp "${tmpdir_svc}/solidinvoice.env" /etc/solidinvoice/solidinvoice.env
        $SUDO chmod 0640 /etc/solidinvoice/solidinvoice.env
    fi

    rm -rf "$tmpdir_svc"
    trap - EXIT

    # Create service user (compatible with both glibc and BusyBox/Alpine)
    if ! getent group solidinvoice >/dev/null 2>&1; then
        if command -v groupadd >/dev/null 2>&1; then
            $SUDO groupadd --system solidinvoice
        else
            $SUDO addgroup -S solidinvoice
        fi
    fi
    if ! getent passwd solidinvoice >/dev/null 2>&1; then
        if command -v useradd >/dev/null 2>&1; then
            $SUDO useradd --system --gid solidinvoice \
                --home-dir /var/lib/solidinvoice --no-create-home \
                --shell /usr/sbin/nologin \
                --comment "SolidInvoice service account" solidinvoice
        else
            $SUDO adduser -S -G solidinvoice -h /var/lib/solidinvoice \
                -s /usr/sbin/nologin -D solidinvoice
        fi
    fi

    $SUDO mkdir -p /var/lib/solidinvoice
    $SUDO chown solidinvoice:solidinvoice /var/lib/solidinvoice
    $SUDO chown root:solidinvoice /etc/solidinvoice
    $SUDO chmod 0750 /etc/solidinvoice /var/lib/solidinvoice

    $SUDO systemctl daemon-reload

    success "systemd service installed"
    echo ""
    echo "  Start:   sudo systemctl start solidinvoice"
    echo "  Enable:  sudo systemctl enable solidinvoice"
    echo "  Status:  sudo systemctl status solidinvoice"
    echo "  Config:  /etc/solidinvoice/solidinvoice.env"
    echo ""
}

print_next_steps() {
    echo ""
    success "Installation complete!"
    echo ""
    echo "  Run SolidInvoice:"
    echo "    ${BINARY_NAME} run"
    echo ""
    echo "  Then open your browser to the URL shown in the terminal."
    echo ""
    echo "  For more options:"
    echo "    ${BINARY_NAME} run --help"
    echo ""

    # Check if binary is on PATH
    if ! echo "$PATH" | tr ':' '\n' | grep -q "^${INSTALL_DIR}$"; then
        warn "${INSTALL_DIR} is not in your PATH."
        echo "  Add it with:"
        echo "    export PATH=\"${INSTALL_DIR}:\$PATH\""
        echo ""
    fi
}

main() {
    echo ""
    info "SolidInvoice Installer"
    echo ""

    detect_os
    detect_arch
    detect_downloader

    if [ -z "${VERSION:-}" ]; then
        get_latest_version
    fi

    check_existing
    needs_sudo
    install_binary
    install_systemd_service
    print_next_steps
}

main
