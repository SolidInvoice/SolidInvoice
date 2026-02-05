package main

import (
	"context"

	"github.com/caddyserver/caddy/v2"
	"github.com/luno/jettison/log"
	"go.uber.org/zap"
)

type logger struct {
}

func (l logger) Log(_ context.Context, entry log.Entry) string {
	// Use Caddy's logger which will use the configured format (JSON or console)
	vals := make([]zap.Field, 0, len(entry.Parameters)+2)

	for _, param := range entry.Parameters {
		vals = append(vals, zap.String(param.Key, param.Value))
	}

	vals = append(vals, zap.String("source", entry.Source))

	// Use appropriate log level based on string value
	switch entry.Level {
	case log.LevelError:
		caddy.Log().Error(entry.Message, vals...)
	case log.LevelDebug:
		caddy.Log().Debug(entry.Message, vals...)
	default:
		caddy.Log().Info(entry.Message, vals...)
	}

	return entry.Message
}
