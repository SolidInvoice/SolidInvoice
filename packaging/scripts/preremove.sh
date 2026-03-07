#!/bin/sh
set -e

# Stop and disable the service before removal
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet solidinvoice 2>/dev/null; then
        systemctl stop solidinvoice
    fi
    if systemctl is-enabled --quiet solidinvoice 2>/dev/null; then
        systemctl disable solidinvoice
    fi
fi
