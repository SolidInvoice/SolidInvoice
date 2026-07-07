package main

import (
	"context"
	"encoding/json"
	"os"
	"path/filepath"
	"testing"
)

// writeSparseFile creates a sparse file of the requested size — only metadata
// is allocated, so tests can simulate gigabyte-scale app directories without
// the multi-hundred-MB transient allocations that buffered writes would
// produce on CI runners.
func writeSparseFile(t *testing.T, path string, size int64) {
	t.Helper()
	if err := os.MkdirAll(filepath.Dir(path), 0o755); err != nil {
		t.Fatal(err)
	}
	f, err := os.Create(path)
	if err != nil {
		t.Fatal(err)
	}
	defer f.Close()
	if size > 0 {
		if err := f.Truncate(size); err != nil {
			t.Fatal(err)
		}
	}
}

func writeFileBytes(t *testing.T, path string, data []byte) {
	t.Helper()
	if err := os.MkdirAll(filepath.Dir(path), 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(path, data, 0o644); err != nil {
		t.Fatal(err)
	}
}

func TestCleanupSkipsWhenBelowThreshold(t *testing.T) {
	home := t.TempDir()
	t.Setenv("HOME", home)
	root := filepath.Join(home, "."+appName)
	writeSparseFile(t, filepath.Join(root, "app_old", "Caddyfile"), 1024)
	if err := cleanupOldVersions(context.Background()); err != nil {
		t.Fatal(err)
	}
	if _, err := os.Stat(filepath.Join(root, "app_old")); err != nil {
		t.Errorf("expected app_old preserved (below threshold), got %v", err)
	}
}

func TestCleanupRetainsEmbeddedActiveAndRecent(t *testing.T) {
	home := t.TempDir()
	t.Setenv("HOME", home)
	root := filepath.Join(home, "."+appName)
	mkapp := func(name string, embedded bool) string {
		dir := filepath.Join(root, name)
		// Sparse 150MiB — total across all five dirs blows past the 500MiB
		// threshold without actually consuming the disk.
		writeSparseFile(t, filepath.Join(dir, "big.dat"), 150*1024*1024)
		if embedded {
			writeFileBytes(t, filepath.Join(dir, embeddedMarkerFile), []byte{1})
		}
		return dir
	}
	embeddedDir := mkapp("app_embedded", true)
	activeDir := mkapp("app_active", false)
	prev1Dir := mkapp("app_prev1", false)
	prev2Dir := mkapp("app_prev2", false)
	oldDir := mkapp("app_old", false)

	active := &activeVersion{
		Version: "3.0.2", Path: activeDir,
		Previous: []previousEntry{
			{Version: "3.0.1", Path: prev1Dir},
			{Version: "3.0.0", Path: prev2Dir},
		},
	}
	data, err := json.Marshal(active)
	if err != nil {
		t.Fatalf("marshal active: %v", err)
	}
	if err := os.WriteFile(filepath.Join(root, activeMarkerFile), data, 0o644); err != nil {
		t.Fatal(err)
	}

	if err := cleanupOldVersions(context.Background()); err != nil {
		t.Fatal(err)
	}

	for _, dir := range []string{embeddedDir, activeDir, prev1Dir, prev2Dir} {
		if _, err := os.Stat(dir); err != nil {
			t.Errorf("expected %s retained: %v", dir, err)
		}
	}
	if _, err := os.Stat(oldDir); !os.IsNotExist(err) {
		t.Errorf("expected %s removed, got %v", oldDir, err)
	}
}
