#!/bin/sh
# Cloudsmith Repository Setup Guide for SolidInvoice
#
# This script documents the steps needed to set up Cloudsmith repositories
# for hosting APT, YUM/DNF, and Alpine packages.
#
# Cloudsmith is free for open-source projects and provides:
# - APT repositories (Debian, Ubuntu)
# - RPM repositories (RHEL, CentOS, Fedora, openSUSE)
# - Alpine APK repositories
# - Automatic GPG signing
# - CDN-backed distribution
# - Package version management
#
# Prerequisites:
# 1. Create a Cloudsmith account: https://cloudsmith.io
# 2. Install the Cloudsmith CLI: pip install cloudsmith-cli
# 3. Authenticate: cloudsmith login
#
# ============================================================================
# STEP 1: Create Organization and Repository
# ============================================================================
#
# Via the Cloudsmith web UI:
# 1. Create organization "solidinvoice" (or use existing)
# 2. Create repository "solidinvoice"
# 3. Set repository type to "Open-Source" (free tier)
# 4. Enable formats: Debian/Ubuntu, RedHat, Alpine
#
# ============================================================================
# STEP 2: Generate API Key
# ============================================================================
#
# 1. Go to Account Settings > API Keys
# 2. Create a new API key with write access to the repository
# 3. Add to GitHub repository secrets as: CLOUDSMITH_API_KEY
#
# ============================================================================
# STEP 3: Upload Packages (manual test)
# ============================================================================
#
# Debian/Ubuntu (.deb):
#   cloudsmith push deb solidinvoice/solidinvoice/any-distro/any-version \
#     dist/solidinvoice_VERSION_amd64.deb
#
# RPM (.rpm):
#   cloudsmith push rpm solidinvoice/solidinvoice/any-distro/any-version \
#     dist/solidinvoice-VERSION.x86_64.rpm
#
# Alpine (.apk):
#   cloudsmith push alpine solidinvoice/solidinvoice/alpine/any-version \
#     dist/solidinvoice-VERSION.apk
#
# ============================================================================
# STEP 4: User Installation Instructions
# ============================================================================
#
# Debian/Ubuntu (APT):
#   curl -1sLf 'https://dl.cloudsmith.io/public/solidinvoice/solidinvoice/setup.deb.sh' | sudo -E bash
#   sudo apt install solidinvoice
#
# RHEL/CentOS/Fedora (YUM/DNF):
#   curl -1sLf 'https://dl.cloudsmith.io/public/solidinvoice/solidinvoice/setup.rpm.sh' | sudo -E bash
#   sudo dnf install solidinvoice
#
# Alpine (APK):
#   curl -1sLf 'https://dl.cloudsmith.io/public/solidinvoice/solidinvoice/setup.alpine.sh' | sudo -E sh
#   sudo apk add solidinvoice
#
# ============================================================================
# STEP 5: GPG Signing (Cloudsmith handles this automatically)
# ============================================================================
#
# Cloudsmith automatically signs packages with a GPG key.
# The public key is included in the setup scripts above.
# No additional GPG configuration is needed.
#
# ============================================================================

echo "This is a documentation script. Please read the comments for setup instructions."
echo ""
echo "Quick start:"
echo "  1. Create Cloudsmith account at https://cloudsmith.io"
echo "  2. Create org 'solidinvoice' and repo 'solidinvoice'"
echo "  3. Generate API key and add as CLOUDSMITH_API_KEY GitHub secret"
echo "  4. The publish-packages workflow will handle the rest"
