package main

import (
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

func isNewer(latest, current string) bool {
	l, err := semver.NewVersion(latest)
	if err != nil {
		return false
	}
	c, err := semver.NewVersion(current)
	if err != nil {
		return true
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

func downloadFile(ctx context.Context, client *http.Client, url, dest string) error {
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
	if _, err := io.Copy(f, resp.Body); err != nil {
		f.Close()
		return err
	}
	return f.Close()
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

func extractTarball(tarPath, destDir string) error {
	data, err := os.ReadFile(tarPath)
	if err != nil {
		return err
	}
	app, err := gUnzipData(data)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(destDir, 0o755); err != nil {
		return err
	}
	if err := untar(app, destDir); err != nil {
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

func sendReloadInProc(newPath string) error {
	if err := os.Setenv("APP_PATH", newPath); err != nil {
		return err
	}
	if err := os.Chdir(newPath); err != nil {
		return err
	}
	config, _, _, err := caddycmd.LoadConfig(filepath.Join(newPath, "Caddyfile"), "")
	if err != nil {
		return fmt.Errorf("load Caddyfile: %w", err)
	}
	if err := caddy.Load(config, true); err != nil {
		return fmt.Errorf("reload caddy: %w", err)
	}
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
	client := &http.Client{Timeout: 60 * time.Second}
	info, err := fetchLatestRelease(ctx, client)
	if err != nil {
		return fmt.Errorf("fetch latest release: %w", err)
	}

	currentVersion := strings.TrimSpace(string(embeddedAppVersion))
	if active, loadErr := loadActiveVersion(); loadErr == nil && active.Version != "" {
		currentVersion = active.Version
	}
	if !isNewer(info.Version, currentVersion) {
		log.Info(ctx, fmt.Sprintf("auto-update: already on latest version (%s)", currentVersion))
		return nil
	}
	log.Info(ctx, fmt.Sprintf("auto-update: new version available %s (current %s)", info.Version, currentVersion))

	stagingDir := filepath.Join(root, stagingDirName)
	tarPath, sum, err := downloadAndVerify(ctx, client, info, stagingDir)
	if err != nil {
		return err
	}
	defer os.Remove(tarPath)

	stagedPath := filepath.Join(root, "app_"+sum)
	if err := extractTarball(tarPath, stagedPath); err != nil {
		return fmt.Errorf("extract: %w", err)
	}
	if err := validateStagedApp(stagedPath); err != nil {
		os.RemoveAll(stagedPath)
		return err
	}

	if err := runStagedMigrations(stagedPath); err != nil {
		log.Error(ctx, fmt.Errorf("auto-update: staged migrations failed, aborting activation: %w", err))
		return err
	}

	prior, _ := loadActiveVersion()
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
