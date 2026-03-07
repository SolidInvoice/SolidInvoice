#!/bin/sh
set -e

# Reload systemd after service file removal
if command -v systemctl >/dev/null 2>&1; then
    systemctl daemon-reload
fi

# Note: We intentionally do NOT remove the solidinvoice user, group,
# /etc/solidinvoice, or /var/lib/solidinvoice on uninstall.
# This preserves configuration and data for reinstallation.
# Users can manually remove these if desired:
#   sudo userdel solidinvoice
#   sudo groupdel solidinvoice
#   sudo rm -rf /etc/solidinvoice /var/lib/solidinvoice
