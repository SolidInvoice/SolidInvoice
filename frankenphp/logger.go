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
	var vals []zap.Field

	for _, param := range entry.Parameters {
		vals = append(vals, zap.String(param.Key, param.Value))
	}

	vals = append(vals, zap.String("level", string(entry.Level)))
	vals = append(vals, zap.String("source", entry.Source))

	caddy.Log().Info(entry.Message, vals...)

	return entry.Message
}
