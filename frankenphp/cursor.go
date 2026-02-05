package main

import (
	"context"
	"sync"
)

type memStore struct {
	cursors sync.Map
}

func (s *memStore) Get(ctx context.Context, name string) (string, error) {
	if value, ok := s.cursors.Load(name); ok {
		str, ok := value.(string)
		if !ok {
			// Don't crash - just return empty string
			return "", nil
		}
		return str, nil
	}

	return "", nil
}

func (s *memStore) Set(ctx context.Context, name, value string) error {
	s.cursors.Store(name, value)
	return nil
}
