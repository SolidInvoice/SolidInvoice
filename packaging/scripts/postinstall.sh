#!/bin/sh
set -e

# Create solidinvoice system user and group if they don't exist
# Supports both glibc-based distros (groupadd/useradd) and Alpine/BusyBox (addgroup/adduser)
if ! getent group solidinvoice >/dev/null 2>&1; then
    if command -v groupadd >/dev/null 2>&1; then
        groupadd --system solidinvoice
    else
        addgroup -S solidinvoice
    fi
fi

if ! getent passwd solidinvoice >/dev/null 2>&1; then
    if command -v useradd >/dev/null 2>&1; then
        useradd --system \
            --gid solidinvoice \
            --home-dir /var/lib/solidinvoice \
            --no-create-home \
            --shell /usr/sbin/nologin \
            --comment "SolidInvoice service account" \
            solidinvoice
    else
        adduser -S -G solidinvoice -h /var/lib/solidinvoice \
            -s /usr/sbin/nologin -D solidinvoice
    fi
fi

# Ensure directories exist with correct ownership
install -d -m 0750 -o solidinvoice -g solidinvoice /var/lib/solidinvoice
install -d -m 0750 -o root -g solidinvoice /etc/solidinvoice

# Fix ownership on config files
if [ -f /etc/solidinvoice/solidinvoice.env ]; then
    chown root:solidinvoice /etc/solidinvoice/solidinvoice.env
    chmod 0640 /etc/solidinvoice/solidinvoice.env
fi

# Reload systemd to pick up the new service file
if command -v systemctl >/dev/null 2>&1; then
    systemctl daemon-reload
    echo ""
    echo "SolidInvoice has been installed successfully."
    echo ""
    echo "To start the service:"
    echo "  sudo systemctl start solidinvoice"
    echo ""
    echo "To enable the service on boot:"
    echo "  sudo systemctl enable solidinvoice"
    echo ""
    echo "Configuration: /etc/solidinvoice/solidinvoice.env"
    echo ""
fi
