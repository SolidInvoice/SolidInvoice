package main

import (
	"archive/tar"
	"compress/gzip"
	"context"
	"crypto/sha256"
	"crypto/subtle"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/Masterminds/semver/v3"
	"github.com/caddyserver/caddy/v2"
	caddycmd "github.com/caddyserver/caddy/v2/cmd"
	"github.com/luno/jettison/log"
)

const (
	autoUpdateDisableEnv  = "SOLIDINVOICE_DISABLE_AUTO_UPDATE"
	autoUpdateAPIBaseEnv  = "SOLIDINVOICE_UPDATE_API_BASE"
	autoUpdateCheckNowEnv = "SOLIDINVOICE_UPDATE_CHECK_NOW"
	defaultUpdateAPIBase  = "https://api.github.com"
	defaultUpdateRepo     = "solidinvoice/solidinvoice"
	activeMarkerFile      = "active.json"
	embeddedMarkerFile    = ".embedded"
	stagingDirName        = ".staging"
	previousCap           = 5
	tarballAssetSuffix    = ".tar.gz"
	sha256AssetSuffix     = ".tar.gz.sha256"

	// maxTarballBytes caps how much we will stream to disk during a release
	// download. 2 GiB is a generous ceiling for a PHP application bundle and
	// guards against a malicious or misconfigured origin streaming
	// unbounded bytes.
	maxTarballBytes = 2 << 30

	// downloadTimeout is the per-attempt context timeout for a release
	// download. It must accommodate large tarballs over slow links —
	// 30 minutes is well above any realistic legitimate transfer.
	downloadTimeout = 30 * time.Minute
	apiTimeout      = 60 * time.Second
)

type releaseAsset struct {
	Name               string `json:"name"`
	BrowserDownloadURL string `json:"browser_download_url"`
}

type githubRelease struct {
	TagName     string         `json:"tag_name"`
	Draft       bool           `json:"draft"`
	Prerelease  bool           `json:"prerelease"`
	PublishedAt string         `json:"published_at"`
	Assets      []releaseAsset `json:"assets"`
}

type releaseInfo struct {
	Version     string
	TarballURL  string
	SHA256URL   string
	PublishedAt string
}

type previousEntry struct {
	Version string `json:"version"`
	Path    string `json:"path"`
}

type activeVersion struct {
	Version        string          `json:"version"`
	Path           string          `json:"path"`
	VerifiedSHA256 string          `json:"verified_sha256"`
	AppliedAt      string          `json:"applied_at"`
	Previous       []previousEntry `json:"previous,omitempty"`
}

func autoUpdateDisabled() bool {
	return os.Getenv(autoUpdateDisableEnv) == "1"
}

func solidInvoiceRoot() (string, error) {
	home, err := os.UserHomeDir()
	if err != nil {
		return "", err
	}
	return filepath.Join(home, "."+appName), nil
}

func activeMarkerPath() (string, error) {
	root, err := solidInvoiceRoot()
	if err != nil {
		return "", err
	}
	return filepath.Join(root, activeMarkerFile), nil
}

func loadActiveVersion() (*activeVersion, error) {
	path, err := activeMarkerPath()
	if err != nil {
		return nil, err
	}
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}
	var v activeVersion
	if err := json.Unmarshal(data, &v); err != nil {
		return nil, err
	}
	return &v, nil
}

func saveActiveVersion(v *activeVersion) error {
	path, err := activeMarkerPath()
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(path), 0o755); err != nil {
		return err
	}
	if len(v.Previous) > previousCap {
		v.Previous = v.Previous[:previousCap]
	}
	data, err := json.MarshalIndent(v, "", "  ")
	if err != nil {
		return err
	}
	tmp := path + ".tmp"
	if err := os.WriteFile(tmp, data, 0o644); err != nil {
		return err
	}
	return os.Rename(tmp, path)
}

func fetchLatestRelease(ctx context.Context, client *http.Client) (*releaseInfo, error) {
	base := os.Getenv(autoUpdateAPIBaseEnv)
	if base == "" {
		base = defaultUpdateAPIBase
	}
	url := fmt.Sprintf("%s/repos/%s/releases/latest", strings.TrimRight(base, "/"), defaultUpdateRepo)
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Accept", "application/vnd.github+json")
	if token := os.Getenv("GITHUB_TOKEN"); token != "" {
		req.Header.Set("Authorization", "Bearer "+token)
	}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(io.LimitReader(resp.Body, 1024))
		return nil, fmt.Errorf("github releases API returned %d: %s", resp.StatusCode, string(body))
	}
	var rel githubRelease
	if err := json.NewDecoder(resp.Body).Decode(&rel); err != nil {
		return nil, err
	}
	if rel.Draft || rel.Prerelease {
		return nil, fmt.Errorf("latest release is draft or prerelease: %s", rel.TagName)
	}
	info := &releaseInfo{Version: strings.TrimPrefix(rel.TagName, "v"), PublishedAt: rel.PublishedAt}
	for _, a := range rel.Assets {
		switch {
		case strings.HasSuffix(a.Name, sha256AssetSuffix):
			info.SHA256URL = a.BrowserDownloadURL
		case strings.HasSuffix(a.Name, tarballAssetSuffix):
			info.TarballURL = a.BrowserDownloadURL
		}
	}
	if info.TarballURL == "" || info.SHA256URL == "" {
		return nil, fmt.Errorf("release %s missing tarball or sha256 asset", rel.TagName)
	}
	return info, nil
}

// isNewer reports whether latest is strictly greater than current under
// semver. Unparseable inputs default to "no update": treating a malformed
// current version as always-older would let any release replace a dev build,
// which we don't want during local development or partially-built CI images.
func isNewer(latest, current string) bool {
	l, err := semver.NewVersion(latest)
	if err != nil {
		return false
	}
	c, err := semver.NewVersion(current)
	if err != nil {
		return false
	}
	return l.GreaterThan(c)
}

func downloadAndVerify(ctx context.Context, client *http.Client, info *releaseInfo, stagingDir string) (string, string, error) {
	if err := os.MkdirAll(stagingDir, 0o755); err != nil {
		return "", "", err
	}
	tarPath := filepath.Join(stagingDir, info.Version+".tar.gz")
	if err := downloadFile(ctx, client, info.TarballURL, tarPath); err != nil {
		return "", "", fmt.Errorf("download tarball: %w", err)
	}
	expected, err := fetchSHA256(ctx, client, info.SHA256URL)
	if err != nil {
		os.Remove(tarPath)
		return "", "", fmt.Errorf("fetch sha256: %w", err)
	}
	actual, err := hashFile(tarPath)
	if err != nil {
		os.Remove(tarPath)
		return "", "", fmt.Errorf("hash tarball: %w", err)
	}
	if subtle.ConstantTimeCompare([]byte(expected), []byte(actual)) != 1 {
		os.Remove(tarPath)
		return "", "", fmt.Errorf("sha256 mismatch: expected %s, got %s", expected, actual)
	}
	return tarPath, actual, nil
}

// downloadFile streams a release asset to dest. The body is bounded by
// maxTarballBytes so a misbehaving origin can't fill the disk; the
// destination file is removed on any error so failures don't leave partial
// files behind for `cleanupOldVersions` to eventually trip over.
func downloadFile(ctx context.Context, client *http.Client, url, dest string) (err error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return err
	}
	if token := os.Getenv("GITHUB_TOKEN"); token != "" {
		req.Header.Set("Authorization", "Bearer "+token)
	}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("download %s: %d", url, resp.StatusCode)
	}
	f, err := os.Create(dest)
	if err != nil {
		return err
	}
	ok := false
	defer func() {
		closeErr := f.Close()
		if !ok {
			_ = os.Remove(dest)
			return
		}
		if err == nil {
			err = closeErr
		}
	}()
	n, copyErr := io.Copy(f, io.LimitReader(resp.Body, maxTarballBytes+1))
	if copyErr != nil {
		return copyErr
	}
	if n > maxTarballBytes {
		return fmt.Errorf("download exceeded %d byte limit", maxTarballBytes)
	}
	ok = true
	return nil
}

func fetchSHA256(ctx context.Context, client *http.Client, url string) (string, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return "", err
	}
	if token := os.Getenv("GITHUB_TOKEN"); token != "" {
		req.Header.Set("Authorization", "Bearer "+token)
	}
	resp, err := client.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()
	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("sha256 fetch returned %d", resp.StatusCode)
	}
	body, err := io.ReadAll(io.LimitReader(resp.Body, 1024))
	if err != nil {
		return "", err
	}
	fields := strings.Fields(string(body))
	if len(fields) == 0 {
		return "", errors.New("empty sha256 file")
	}
	h := strings.ToLower(fields[0])
	if len(h) != 64 {
		return "", fmt.Errorf("invalid sha256 hash length: %d", len(h))
	}
	return h, nil
}

func hashFile(path string) (string, error) {
	f, err := os.Open(path)
	if err != nil {
		return "", err
	}
	defer f.Close()
	h := sha256.New()
	if _, err := io.Copy(h, f); err != nil {
		return "", err
	}
	return hex.EncodeToString(h.Sum(nil)), nil
}

// extractTarball streams the gzipped tar from disk directly into destDir,
// without ever holding the full archive in memory. destDir is cleaned first
// so a previous partial extraction can't leave stale files mixed in with the
// new version.
func extractTarball(tarPath, destDir string) error {
	if err := os.RemoveAll(destDir); err != nil {
		return err
	}
	if err := os.MkdirAll(destDir, 0o755); err != nil {
		return err
	}
	f, err := os.Open(tarPath)
	if err != nil {
		return err
	}
	defer f.Close()
	gz, err := gzip.NewReader(f)
	if err != nil {
		return err
	}
	defer gz.Close()
	if err := untarStream(tar.NewReader(gz), destDir); err != nil {
		os.RemoveAll(destDir)
		return err
	}
	return nil
}

func validateStagedApp(path string) error {
	for _, rel := range []string{"Caddyfile", "bin/console", "public/index.php"} {
		if _, err := os.Stat(filepath.Join(path, rel)); err != nil {
			return fmt.Errorf("staged app missing %s: %w", rel, err)
		}
	}
	return nil
}

func runStagedMigrations(stagedPath string) error {
	return runConsoleCommandWithEnv(
		map[string]string{"APP_PATH": stagedPath},
		"doctrine:migrations:migrate",
	)
}

// sendReloadInProc atomically switches the running process to a new staged
// app. Caddy config is loaded and parsed FIRST so a malformed Caddyfile is
// caught before any global state changes; only if validation succeeds do we
// flip APP_PATH and the working directory. Any subsequent failure rolls
// those back so the process never lands in a half-switched state.
func sendReloadInProc(newPath string) (err error) {
	config, _, _, err := caddycmd.LoadConfig(filepath.Join(newPath, "Caddyfile"), "")
	if err != nil {
		return fmt.Errorf("load Caddyfile: %w", err)
	}

	prevPath := os.Getenv("APP_PATH")
	prevWD, _ := os.Getwd()

	if err := os.Setenv("APP_PATH", newPath); err != nil {
		return err
	}
	if err := os.Chdir(newPath); err != nil {
		_ = os.Setenv("APP_PATH", prevPath)
		return err
	}

	committed := false
	defer func() {
		if committed {
			return
		}
		_ = os.Setenv("APP_PATH", prevPath)
		if prevWD != "" {
			_ = os.Chdir(prevWD)
		}
	}()

	if err := caddy.Load(config, true); err != nil {
		return fmt.Errorf("reload caddy: %w", err)
	}
	committed = true

	if err := runConsoleCommand("cache:clear"); err != nil {
		return fmt.Errorf("clear cache: %w", err)
	}
	if err := runConsoleCommand("messenger:stop-workers"); err != nil {
		log.Error(nil, fmt.Errorf("stop messenger workers (non-fatal): %w", err))
	}
	return nil
}

func checkAndApplyUpdate(ctx context.Context) error {
	if autoUpdateDisabled() {
		return nil
	}
	root, err := solidInvoiceRoot()
	if err != nil {
		return err
	}

	apiCtx, cancel := context.WithTimeout(ctx, apiTimeout)
	info, fetchErr := fetchLatestRelease(apiCtx, http.DefaultClient)
	cancel()
	if fetchErr != nil {
		return fmt.Errorf("fetch latest release: %w", fetchErr)
	}

	// Capture active version once and reuse it for both the version
	// comparison and the "previous" history below. Re-reading later would
	// race with any concurrent writer (TOCTOU).
	// We also only trust active.Version if its on-disk app validates —
	// otherwise a corrupt active.json could indefinitely suppress updates.
	prior, _ := loadActiveVersion()
	currentVersion := strings.TrimSpace(string(embeddedAppVersion))
	if prior != nil && prior.Version != "" && validateStagedApp(prior.Path) == nil {
		currentVersion = prior.Version
	} else if prior != nil && prior.Version != "" {
		log.Info(ctx, fmt.Sprintf("auto-update: active.json points at invalid path %s, comparing against embedded version", prior.Path))
	}

	if !isNewer(info.Version, currentVersion) {
		log.Info(ctx, fmt.Sprintf("auto-update: already on latest version (%s)", currentVersion))
		return nil
	}
	log.Info(ctx, fmt.Sprintf("auto-update: new version available %s (current %s)", info.Version, currentVersion))

	stagingDir := filepath.Join(root, stagingDirName)
	dlCtx, dlCancel := context.WithTimeout(ctx, downloadTimeout)
	defer dlCancel()
	tarPath, sum, err := downloadAndVerify(dlCtx, http.DefaultClient, info, stagingDir)
	if err != nil {
		return err
	}
	// Best-effort cleanup of the staging directory once we've consumed the
	// tarball. Defer ensures it runs on every exit path below.
	defer func() {
		_ = os.Remove(tarPath)
		_ = os.RemoveAll(stagingDir)
	}()

	stagedPath := filepath.Join(root, "app_"+sum)
	if err := extractTarball(tarPath, stagedPath); err != nil {
		_ = os.RemoveAll(stagedPath)
		return fmt.Errorf("extract: %w", err)
	}
	if err := validateStagedApp(stagedPath); err != nil {
		_ = os.RemoveAll(stagedPath)
		return err
	}

	if err := runStagedMigrations(stagedPath); err != nil {
		log.Error(ctx, fmt.Errorf("auto-update: staged migrations failed, aborting activation: %w", err))
		_ = os.RemoveAll(stagedPath)
		return err
	}

	next := &activeVersion{
		Version:        info.Version,
		Path:           stagedPath,
		VerifiedSHA256: sum,
		AppliedAt:      time.Now().UTC().Format(time.RFC3339),
	}
	if prior != nil && prior.Path != "" {
		next.Previous = append([]previousEntry{{Version: prior.Version, Path: prior.Path}}, prior.Previous...)
	}
	if err := saveActiveVersion(next); err != nil {
		return fmt.Errorf("save active version: %w", err)
	}

	if err := sendReloadInProc(stagedPath); err != nil {
		log.Error(ctx, fmt.Errorf("auto-update: live reload failed (will apply on next restart): %w", err))
		return nil
	}
	log.Info(ctx, fmt.Sprintf("auto-update: activated version %s", info.Version))
	return nil
}
