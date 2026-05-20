package main

import (
	"context"
	"io/fs"
	"os"
	"path/filepath"
	"strings"

	"github.com/luno/jettison/log"
)

const (
	cleanupMinTotalSize   = 500 * 1024 * 1024
	previousKeepOnCleanup = 2
)

func cleanupOldVersions(ctx context.Context) error {
	root, err := solidInvoiceRoot()
	if err != nil {
		return err
	}
	entries, err := os.ReadDir(root)
	if err != nil {
		if os.IsNotExist(err) {
			return nil
		}
		return err
	}

	var appDirs []string
	var totalSize int64
	thresholdReached := false
	for _, e := range entries {
		if !e.IsDir() || !strings.HasPrefix(e.Name(), "app_") {
			continue
		}
		full := filepath.Join(root, e.Name())
		appDirs = append(appDirs, full)
		if thresholdReached {
			continue
		}
		size, sizeErr := dirSize(full)
		if sizeErr != nil {
			log.Error(ctx, sizeErr)
			continue
		}
		totalSize += size
		if totalSize >= cleanupMinTotalSize {
			thresholdReached = true
		}
	}

	if !thresholdReached {
		return nil
	}

	keep := map[string]bool{}
	for _, dir := range appDirs {
		if _, err := os.Stat(filepath.Join(dir, embeddedMarkerFile)); err == nil {
			keep[dir] = true
		}
	}
	if active, err := loadActiveVersion(); err == nil && active != nil {
		if active.Path != "" {
			keep[active.Path] = true
		}
		for i, prev := range active.Previous {
			if i >= previousKeepOnCleanup {
				break
			}
			if prev.Path != "" {
				keep[prev.Path] = true
			}
		}
	}

	for _, dir := range appDirs {
		if keep[dir] {
			continue
		}
		log.Info(ctx, "auto-update cleanup: removing "+dir)
		if err := os.RemoveAll(dir); err != nil {
			log.Error(ctx, err)
		}
	}
	return nil
}

func dirSize(path string) (int64, error) {
	var size int64
	err := filepath.WalkDir(path, func(_ string, d fs.DirEntry, err error) error {
		if err != nil {
			return err
		}
		if !d.IsDir() {
			info, err := d.Info()
			if err != nil {
				return err
			}
			size += info.Size()
		}
		return nil
	})
	return size, err
}
