# SolidInvoice Build Scripts Guide

This guide explains how to use the improved build scripts for both local development/testing and production releases.

## Quick Start

### Local Development Build
```bash
# Build everything (dist + binary) using local files
./scripts/build_binary.sh --local

# Build just the dist archive using local files
./scripts/build_dist.sh --local

# Build specific branch locally
./scripts/build_binary.sh feature-branch --local
```

### Production Release Build
```bash
# Build from remote repository (clones from GitHub)
./scripts/build_binary.sh v2.3.0

# Or build dist and binary separately
./scripts/build_dist.sh 2.3.x v2.3.0
./scripts/build_binary.sh v2.3.0

# Release to GitHub (requires gh CLI and RELEASE=1)
RELEASE=1 ./scripts/build_binary.sh v2.3.0
```

## Build Scripts Overview

### 1. `build_dist.sh` - Distribution Archive Builder

Builds the distribution archives (`.tar.gz` and `.zip`) containing the application code, dependencies, and compiled assets.

**Usage:**
```bash
./scripts/build_dist.sh [BRANCH] [VERSION] [--local]
```

**Arguments:**
- `BRANCH` - Branch or tag to checkout (default: current branch)
- `VERSION` - Version number for the archive filename (default: branch name or commit SHA)
- `--local` - Use local filesystem instead of cloning from GitHub

**Examples:**
```bash
# Dev build of current branch using local files
./scripts/build_dist.sh --local

# Dev build of specific branch locally
./scripts/build_dist.sh feature-auth --local

# Production build from remote repo
./scripts/build_dist.sh 2.3.x v2.3.0

# Interactive mode (will prompt for inputs)
./scripts/build_dist.sh
```

**What it does:**
1. Clones from GitHub (or copies local files with `--local`)
2. Installs production PHP dependencies (`composer install --no-dev`)
3. Installs and builds frontend assets (`bun install && bun run build`)
4. Creates `.zip` and `.tar.gz` archives in `build/dist/`
5. Optionally uploads to GitHub releases (if `RELEASE=1`)

**Output:**
- `build/dist/SolidInvoice-{VERSION}.tar.gz`
- `build/dist/SolidInvoice-{VERSION}.zip`

### 2. `build_binary.sh` - Binary Builder Wrapper

Builds the standalone binary (FrankenPHP static build). Automatically builds the dist archive if needed.

**Usage:**
```bash
./scripts/build_binary.sh [VERSION] [--local] [--skip-dist]
```

**Arguments:**
- `VERSION` - Version number for the binary (default: current branch name)
- `--local` - Use local filesystem (passed to `build_dist.sh`)
- `--skip-dist` - Skip building dist archive (assumes it already exists)

**Examples:**
```bash
# Build everything locally (recommended for dev)
./scripts/build_binary.sh --local

# Build with existing dist archive
./scripts/build_binary.sh --skip-dist

# Production build
./scripts/build_binary.sh v2.3.0

# Dev build of specific branch
./scripts/build_binary.sh feature-auth --local
```

**What it does:**
1. Checks if dist archive exists
2. If not found (and `--skip-dist` not used), automatically runs `build_dist.sh`
3. Copies dist archive to `frankenphp/app.tar.gz`
4. Runs `frankenphp/build-static.sh` to build the binary
5. Optionally uploads to GitHub releases (if `RELEASE=1`)

**Output:**
- `frankenphp/dist/solidinvoice-{os}-{arch}` (e.g., `solidinvoice-linux-x86_64`)

### 3. `frankenphp/build-static.sh` - Static Binary Builder

Low-level script that builds the FrankenPHP static binary. Usually called by `build_binary.sh`.

**Environment Variables:**
- `SOLIDINVOICE_VERSION` - Version for the binary (default: current branch or commit SHA)
- `PHP_VERSION` - PHP version to build (default: latest 8.3.x)
- `RELEASE` - Set to `1` to upload to GitHub releases
- `DEBUG_SYMBOLS` - Set to `1` to include debug symbols
- `CLEAN` - Set to `1` to clean build cache

**What it does:**
1. Detects OS and architecture
2. Downloads/builds static-php-cli
3. Compiles PHP with required extensions
4. Builds Go binary with embedded PHP
5. Optionally compresses with UPX
6. Optionally uploads to GitHub releases

## Common Scenarios

### Scenario 1: Testing Local Changes
You've made code changes and want to test the full build:

```bash
# Option A: Build everything (recommended)
./scripts/build_binary.sh --local

# Option B: Just test dist archive creation
./scripts/build_dist.sh --local
```

**Advantages of `--local`:**
- No need to commit/push changes
- Faster (no git clone)
- Tests exactly what's in your working directory
- Uses local uncommitted changes

### Scenario 2: Testing Specific Branch
You want to test a feature branch:

```bash
# From any directory
cd /path/to/solidinvoice
git checkout feature-auth
./scripts/build_binary.sh --local

# Or specify branch explicitly
./scripts/build_binary.sh feature-auth --local
```

### Scenario 3: Production Release
Building official release artifacts:

```bash
# Build v2.3.0 from remote repo
./scripts/build_binary.sh v2.3.0

# This will:
# 1. Clone from GitHub (tag v2.3.0)
# 2. Build dist archive
# 3. Build binary
# 4. Create artifacts in build/dist/
```

### Scenario 4: Upload to GitHub Release
After building, upload to GitHub:

```bash
# Build and upload in one command
RELEASE=1 ./scripts/build_binary.sh v2.3.0

# Or upload separately after building
gh release upload v2.3.0 build/dist/SolidInvoice-v2.3.0.tar.gz
gh release upload v2.3.0 build/dist/SolidInvoice-v2.3.0.zip
gh release upload v2.3.0 frankenphp/dist/solidinvoice-linux-x86_64
```

### Scenario 5: Iterative Development
You're iterating on build scripts or dist configuration:

```bash
# First build
./scripts/build_dist.sh --local

# Make changes to build config
vim composer.json

# Rebuild (uses updated local files)
./scripts/build_dist.sh --local

# Test binary with updated dist
./scripts/build_binary.sh --skip-dist
```

### Scenario 6: GitHub Actions / CI
In automated environments:

```bash
# Clone is already done by CI
# Use local mode with explicit version
./scripts/build_dist.sh --local --version "${GITHUB_REF_NAME}"
./scripts/build_binary.sh "${GITHUB_REF_NAME}" --skip-dist

# For releases
RELEASE=1 ./scripts/build_binary.sh "${GITHUB_REF_NAME}" --skip-dist
```

## Build Outputs

### Distribution Archives
Located in `build/dist/`:
- `SolidInvoice-{VERSION}.tar.gz` - Gzip compressed tarball
- `SolidInvoice-{VERSION}.zip` - ZIP archive

Both contain the same files:
- PHP application code
- Compiled frontend assets
- Production dependencies
- Configuration templates
- No development dependencies
- No `.git` directory

### Binary Files
Located in `frankenphp/dist/`:
- `solidinvoice-{os}-{arch}` - Platform-specific binary

Examples:
- `solidinvoice-linux-x86_64`
- `solidinvoice-linux-aarch64`
- `solidinvoice-mac-arm64`

Each binary is a self-contained executable with:
- PHP runtime embedded
- Application code embedded
- Caddy web server
- All required extensions

## Version Naming Conventions

The scripts intelligently handle versions:

| Input | Use Case | Resulting Version |
|-------|----------|-------------------|
| (empty) + `--local` | Dev build | Current commit SHA (short) |
| (empty) | Interactive | Prompts for input |
| `feature-auth` + `--local` | Feature branch dev | `feature-auth` |
| `2.3.x` + `--local` | Branch dev | `2.3.x` |
| `v2.3.0` | Release | `v2.3.0` (or `2.3.0` normalized) |
| `abc123def` | Specific commit | `abc123def` |

## Troubleshooting

### "Dist archive not found"
```bash
# Solution: Let build_binary.sh auto-build it
./scripts/build_binary.sh --local

# Or build it explicitly
./scripts/build_dist.sh --local
```

### "Build file does not exist" with `--skip-dist`
```bash
# Remove --skip-dist flag, or build dist first
./scripts/build_dist.sh --local
./scripts/build_binary.sh --skip-dist
```

### Local changes not reflected in build
```bash
# Ensure you're using --local flag
./scripts/build_dist.sh --local  # ✅ Uses local files
./scripts/build_dist.sh           # ❌ Clones from GitHub
```

### Permission errors
```bash
# Make scripts executable
chmod +x scripts/*.sh frankenphp/*.sh
```

### Build cache issues
```bash
# Clean build artifacts
rm -rf build/*

# Clean FrankenPHP build cache
CLEAN=1 ./frankenphp/build-static.sh
```

## Development Workflow

Recommended workflow for development:

1. **Make changes** to code
2. **Test locally** without committing:
   ```bash
   ./scripts/build_binary.sh --local
   ```
3. **Verify binary** works:
   ```bash
   ./frankenphp/dist/solidinvoice-{os}-{arch} version
   ```
4. **Iterate** as needed (repeat steps 1-3)
5. **Commit** when satisfied
6. **Production build** when ready:
   ```bash
   git push
   ./scripts/build_binary.sh v2.3.0
   ```

## Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `SOLIDINVOICE_VERSION` | Version for binary | Current branch/commit |
| `SOLIDINVOICE_ENV` | App environment | `prod` |
| `SOLIDINVOICE_DEBUG` | Debug mode | `0` |
| `NODE_ENVIRONMENT` | Node env | `production` |
| `RELEASE` | Upload to GitHub | `0` |
| `PHP_VERSION` | PHP version to build | Latest 8.3.x |
| `DEBUG_SYMBOLS` | Include debug symbols | `0` |
| `CLEAN` | Clean build cache | `0` |
| `NO_COMPRESS` | Skip UPX compression | `0` |

## Migration from Old Scripts

### Before (Old Way)
```bash
# Had to commit and push first
git add .
git commit -m "Test build changes"
git push

# Then build from remote
./scripts/build_dist.sh 2.3.x test-version
./scripts/build_binary.sh test-version

# Required manual dist build first
# No automatic chaining
# Difficult to iterate
```

### After (New Way)
```bash
# Test without committing
./scripts/build_binary.sh --local

# Auto-builds dist + binary
# Uses local uncommitted changes
# Easy iteration
```

All old functionality is preserved - remote cloning still works for production builds!
