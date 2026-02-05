# Build Scripts

Scripts for building SolidInvoice distribution archives and standalone binaries.

## Quick Reference

### For Local Development/Testing
```bash
# Build everything (dist + binary) using your local files
./scripts/build_binary.sh --local
```

This is the **recommended** way to test builds during development. It:
- ✅ Uses your current working directory (no git clone needed)
- ✅ Includes uncommitted changes
- ✅ Automatically builds dist archive
- ✅ Builds standalone binary
- ✅ Fast iteration

### For Production Releases
```bash
# Build from remote repository
./scripts/build_binary.sh v2.3.0

# Upload to GitHub releases
RELEASE=1 ./scripts/build_binary.sh v2.3.0
```

## Available Scripts

| Script | Purpose | Documentation |
|--------|---------|---------------|
| `build_dist.sh` | Build distribution archives (.tar.gz, .zip) | Run with `--help` |
| `build_binary.sh` | Build standalone FrankenPHP binary | Run with `--help` |

## Get Help

```bash
# Show detailed usage for each script
./scripts/build_dist.sh --help
./scripts/build_binary.sh --help
```

## Complete Documentation

See **[BUILD_GUIDE.md](./BUILD_GUIDE.md)** for:
- Detailed usage examples
- All command-line options
- Common scenarios and workflows
- Troubleshooting guide
- Environment variables reference

## Examples

```bash
# Local development - test your changes
./scripts/build_binary.sh --local

# Build specific branch locally
./scripts/build_binary.sh feature-auth --local

# Just build dist archive
./scripts/build_dist.sh --local

# Production release
./scripts/build_binary.sh v2.3.0

# Rebuild binary without rebuilding dist
./scripts/build_binary.sh --skip-dist
```

## Output Locations

- **Distribution archives**: `build/dist/SolidInvoice-{VERSION}.{tar.gz,zip}`
- **Binary**: `frankenphp/dist/solidinvoice-{os}-{arch}`

## Requirements

- **For dist builds**: PHP 8.4+, Composer, Bun
- **For binary builds**: Go, git, cmake (Linux only)
- **For releases**: GitHub CLI (`gh`)
