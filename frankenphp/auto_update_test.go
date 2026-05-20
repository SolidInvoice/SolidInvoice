package main

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestIsNewer(t *testing.T) {
	cases := []struct {
		latest, current string
		want            bool
	}{
		{"3.0.1", "3.0.0", true},
		{"3.0.0", "3.0.0", false},
		{"3.0.0", "3.0.1", false},
		{"3.0.0", "3.0.0-alpha1", true},
		{"3.0.0-alpha2", "3.0.0-alpha1", true},
		{"v3.0.1", "3.0.0", true},
		{"garbage", "3.0.0", false},
		{"3.0.0", "garbage", true},
	}
	for _, c := range cases {
		if got := isNewer(c.latest, c.current); got != c.want {
			t.Errorf("isNewer(%q, %q) = %v, want %v", c.latest, c.current, got, c.want)
		}
	}
}

func TestFetchLatestRelease(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if !strings.HasSuffix(r.URL.Path, "/releases/latest") {
			http.NotFound(w, r)
			return
		}
		_ = json.NewEncoder(w).Encode(githubRelease{
			TagName:     "v3.1.0",
			Draft:       false,
			Prerelease:  false,
			PublishedAt: "2026-05-13T00:00:00Z",
			Assets: []releaseAsset{
				{Name: "SolidInvoice-v3.1.0.tar.gz", BrowserDownloadURL: "https://example.com/SolidInvoice-v3.1.0.tar.gz"},
				{Name: "SolidInvoice-v3.1.0.tar.gz.sha256", BrowserDownloadURL: "https://example.com/SolidInvoice-v3.1.0.tar.gz.sha256"},
			},
		})
	}))
	defer server.Close()

	t.Setenv(autoUpdateAPIBaseEnv, server.URL)
	info, err := fetchLatestRelease(context.Background(), server.Client())
	if err != nil {
		t.Fatalf("fetchLatestRelease: %v", err)
	}
	if info.Version != "3.1.0" {
		t.Errorf("Version = %q, want 3.1.0", info.Version)
	}
	if !strings.HasSuffix(info.TarballURL, "v3.1.0.tar.gz") {
		t.Errorf("TarballURL = %q", info.TarballURL)
	}
	if !strings.HasSuffix(info.SHA256URL, ".sha256") {
		t.Errorf("SHA256URL = %q", info.SHA256URL)
	}
}

func TestFetchLatestReleaseSkipsPrerelease(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_ = json.NewEncoder(w).Encode(githubRelease{
			TagName: "v3.1.0", Prerelease: true,
			Assets: []releaseAsset{{Name: "x.tar.gz"}, {Name: "x.tar.gz.sha256"}},
		})
	}))
	defer server.Close()
	t.Setenv(autoUpdateAPIBaseEnv, server.URL)
	if _, err := fetchLatestRelease(context.Background(), server.Client()); err == nil {
		t.Fatal("expected error for prerelease, got nil")
	}
}

func TestDownloadAndVerify(t *testing.T) {
	payload := []byte("hello-tarball-bytes")
	// SHA256 of "hello-tarball-bytes" computed below by the function under test.
	tarball := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write(payload)
	}))
	defer tarball.Close()

	// Use an in-test helper to compute the expected hash via the same code path.
	tmp := t.TempDir()
	tmpFile := filepath.Join(tmp, "payload")
	if err := os.WriteFile(tmpFile, payload, 0o644); err != nil {
		t.Fatal(err)
	}
	expected, err := hashFile(tmpFile)
	if err != nil {
		t.Fatal(err)
	}

	shaServer := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte(expected + "  payload\n"))
	}))
	defer shaServer.Close()

	info := &releaseInfo{Version: "1.0.0", TarballURL: tarball.URL, SHA256URL: shaServer.URL}
	stagingDir := filepath.Join(tmp, "staging")
	path, sum, err := downloadAndVerify(context.Background(), http.DefaultClient, info, stagingDir)
	if err != nil {
		t.Fatalf("downloadAndVerify: %v", err)
	}
	if sum != expected {
		t.Errorf("sum = %q, want %q", sum, expected)
	}
	if _, err := os.Stat(path); err != nil {
		t.Errorf("expected staged file %s: %v", path, err)
	}
}

func TestDownloadAndVerifyMismatch(t *testing.T) {
	tarball := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte("actual"))
	}))
	defer tarball.Close()
	shaServer := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte("0000000000000000000000000000000000000000000000000000000000000000  x\n"))
	}))
	defer shaServer.Close()

	info := &releaseInfo{Version: "1.0.0", TarballURL: tarball.URL, SHA256URL: shaServer.URL}
	tmp := t.TempDir()
	if _, _, err := downloadAndVerify(context.Background(), http.DefaultClient, info, tmp); err == nil {
		t.Fatal("expected mismatch error, got nil")
	}
	// File should have been cleaned up.
	if _, err := os.Stat(filepath.Join(tmp, "1.0.0.tar.gz")); !os.IsNotExist(err) {
		t.Errorf("expected staged file removed, got %v", err)
	}
}

func TestSaveAndLoadActiveVersionRoundTrip(t *testing.T) {
	home := t.TempDir()
	t.Setenv("HOME", home)
	v := &activeVersion{
		Version:        "3.1.0",
		Path:           filepath.Join(home, "."+appName, "app_xyz"),
		VerifiedSHA256: "abc",
		AppliedAt:      "2026-05-13T00:00:00Z",
		Previous:       []previousEntry{{Version: "3.0.0", Path: "/x"}},
	}
	if err := saveActiveVersion(v); err != nil {
		t.Fatalf("save: %v", err)
	}
	got, err := loadActiveVersion()
	if err != nil {
		t.Fatalf("load: %v", err)
	}
	if got.Version != v.Version || got.Path != v.Path || got.VerifiedSHA256 != v.VerifiedSHA256 {
		t.Errorf("round-trip mismatch: %+v vs %+v", got, v)
	}
}

func TestSaveActiveVersionCapsPrevious(t *testing.T) {
	home := t.TempDir()
	t.Setenv("HOME", home)
	prev := make([]previousEntry, previousCap+3)
	for i := range prev {
		prev[i] = previousEntry{Version: "v", Path: "/p"}
	}
	v := &activeVersion{Version: "3.1.0", Path: "/x", Previous: prev}
	if err := saveActiveVersion(v); err != nil {
		t.Fatal(err)
	}
	got, err := loadActiveVersion()
	if err != nil {
		t.Fatal(err)
	}
	if len(got.Previous) != previousCap {
		t.Errorf("previous len = %d, want %d", len(got.Previous), previousCap)
	}
}

func TestLoadActiveVersionMissingReturnsError(t *testing.T) {
	home := t.TempDir()
	t.Setenv("HOME", home)
	if _, err := loadActiveVersion(); err == nil {
		t.Fatal("expected error for missing active.json, got nil")
	}
}

func TestValidateStagedAppRejectsIncomplete(t *testing.T) {
	dir := t.TempDir()
	if err := validateStagedApp(dir); err == nil {
		t.Fatal("expected error for empty staged dir, got nil")
	}
	for _, rel := range []string{"Caddyfile", "bin/console", "public/index.php"} {
		if err := os.MkdirAll(filepath.Dir(filepath.Join(dir, rel)), 0o755); err != nil {
			t.Fatal(err)
		}
		if err := os.WriteFile(filepath.Join(dir, rel), []byte("x"), 0o644); err != nil {
			t.Fatal(err)
		}
	}
	if err := validateStagedApp(dir); err != nil {
		t.Fatalf("expected complete staged dir to validate, got %v", err)
	}
}

func TestFetchSHA256ParsesShaSumFormat(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte("e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855  some-file.tar.gz\n"))
	}))
	defer server.Close()
	got, err := fetchSHA256(context.Background(), http.DefaultClient, server.URL)
	if err != nil {
		t.Fatal(err)
	}
	if got != "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855" {
		t.Errorf("got %q", got)
	}
}
