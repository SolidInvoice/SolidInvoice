# Build Binary Workflow Guide

Quick reference for understanding when the dist archive is rebuilt vs reused.

## TL;DR

| Command | Dist Behavior |
|---------|---------------|
| `./build_binary.sh --local` | **ALWAYS rebuilds** (includes latest changes) ✅ |
| `./build_binary.sh --skip-dist` | **Never rebuilds** (must exist) |
| `./build_binary.sh` | **Builds if missing**, reuses if exists |
| `./build_binary.sh v2.3.0` | **Builds if missing**, reuses if exists |

## Detailed Behavior

### 1. Development: Testing Local Changes

```bash
# Make changes to your code
vim src/SomeBundle/SomeFile.php

# Build binary with latest changes
./build_binary.sh --local
```

**What happens:**
1. ✅ Deletes existing dist archive (if any)
2. ✅ Rebuilds dist from local filesystem
3. ✅ Builds binary with new dist
4. ✅ You can test your changes immediately

**Use when:**
- Testing code changes locally
- Iterating on features
- Debugging issues

### 2. Rebuild Binary Only (Skip Dist)

```bash
# Dist already built, just rebuild binary
./build_binary.sh --skip-dist
```

**What happens:**
1. ❌ Skips dist build entirely
2. ✅ Uses existing dist archive
3. ✅ Rebuilds binary
4. ⚠️ **ERROR if dist doesn't exist**

**Use when:**
- Testing binary build flags
- Experimenting with FrankenPHP options
- Dist is already correct

### 3. Production Release Build

```bash
# Clone from GitHub and build
./build_binary.sh v2.3.0
```

**What happens:**
1. ✅ Checks if `build/dist/SolidInvoice-v2.3.0.tar.gz` exists
2. If exists: Reuses it
3. If missing: Clones from GitHub tag `v2.3.0` and builds
4. ✅ Builds binary

**Use when:**
- Creating production releases
- Building from specific tags/versions
- CI/CD pipelines

### 4. First Build (No Dist Exists)

```bash
# First time building
./build_binary.sh
```

**What happens:**
1. ❌ Dist not found
2. ✅ Automatically runs `build_dist.sh`
3. ✅ Builds binary

**Use when:**
- First time building the project
- Clean builds

## Common Workflows

### Daily Development

```bash
# Edit code
vim src/CoreBundle/Action/SomeAction.php

# Test changes
./build_binary.sh --local

# Run binary
./frankenphp/dist/solidinvoice-* run
```

### Testing Performance Tweaks

```bash
# Build dist once
./build_binary.sh --local

# Test different worker configurations (reuse same dist)
PHP_EXTENSIONS="opcache,..." ./build_binary.sh --skip-dist
CLEAN=1 ./build_binary.sh --skip-dist
```

### Release Process

```bash
# Build from GitHub tag
./build_binary.sh v2.3.0

# Upload to releases
RELEASE=1 ./build_binary.sh v2.3.0
```

## Flag Combinations

### Valid Combinations

✅ `./build_binary.sh --local`
- Rebuilds dist from local
- Builds binary

✅ `./build_binary.sh --skip-dist`
- Uses existing dist
- Rebuilds binary

✅ `./build_binary.sh v2.3.0`
- Builds/reuses dist from GitHub
- Builds binary

### Invalid Combinations

❌ `./build_binary.sh --local --skip-dist`
- **Contradictory:** Can't rebuild from local AND skip dist build
- Script will prioritize `--local` (rebuilds dist)

## Performance Optimization

### Fast Iteration (Local Development)

```bash
# First build (slow)
./build_binary.sh --local
# Takes: ~5-10 minutes

# Change code
vim src/CoreBundle/SomeFile.php

# Rebuild with changes (slow - rebuilds dist + binary)
./build_binary.sh --local
# Takes: ~5-10 minutes
```

**Tip:** Each `--local` rebuild takes full time because it:
1. Rebuilds entire dist archive
2. Rebuilds binary

### Skip Dist for Binary-Only Changes

If you're only changing FrankenPHP build config (not app code):

```bash
# Build dist once
./build_binary.sh --local

# Test different binary configs (fast)
export PHP_EXTENSIONS="opcache,pdo_mysql"
./build_binary.sh --skip-dist
# Takes: ~3-5 minutes (no dist rebuild)
```

## File Locations

```
project/
├── build/
│   └── dist/
│       ├── SolidInvoice-{VERSION}.tar.gz  ← Dist archive
│       └── SolidInvoice-{VERSION}.zip
├── frankenphp/
│   ├── app.tar.gz                         ← Copy of dist (for embedding)
│   └── dist/
│       └── solidinvoice-{os}-{arch}       ← Final binary
└── scripts/
    ├── build_binary.sh                     ← This script
    └── build_dist.sh                       ← Builds dist archive
```

## Environment Variables

| Variable | Effect |
|----------|--------|
| `RELEASE=1` | Upload binary to GitHub releases |
| `PHP_VERSION=8.4` | PHP version for binary |
| `DEBUG_SYMBOLS=1` | Include debug symbols |
| `CLEAN=1` | Clean build cache first |

## Troubleshooting

### "Dist archive not found" with --skip-dist

```
Error: Dist archive does not exist and --skip-dist was specified.
Expected: build/dist/SolidInvoice-main.tar.gz
```

**Solution:** Remove `--skip-dist` or build dist first:
```bash
./build_dist.sh --local
./build_binary.sh --skip-dist
```

### Binary doesn't include my changes (with --local)

**Problem:** You used `--skip-dist` by mistake

**Solution:** Use `--local` to rebuild with changes:
```bash
./build_binary.sh --local
```

### Builds taking too long

**For app changes:**
```bash
# Must rebuild dist + binary (slow but necessary)
./build_binary.sh --local
```

**For binary config changes only:**
```bash
# Build dist once
./build_binary.sh --local

# Then test configs quickly
./build_binary.sh --skip-dist
```

## Summary

**Golden Rule:**
- Testing app changes? → Use `--local` (always rebuilds)
- Testing binary configs? → Use `--skip-dist` (fast, reuses dist)
- Production release? → Use version number (efficient caching)

**Examples:**
```bash
# Development (includes latest changes)
./build_binary.sh --local

# Quick binary rebuild (no app changes)
./build_binary.sh --skip-dist

# Production
./build_binary.sh v2.3.0
```
