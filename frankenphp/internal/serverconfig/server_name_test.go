package serverconfig_test

import (
	"strings"
	"testing"

	"solidinvoice/internal/serverconfig"
)

func TestBuildServerName(t *testing.T) {
	tests := []struct {
		name    string
		params  serverconfig.Params
		want    string
		wantErr bool
	}{
		{
			name: "domain with self-signed https",
			params: serverconfig.Params{
				Domain:   "example.com",
				HttpPort: "8765",
			},
			want: "https://example.com:8765",
		},
		{
			name: "domain with lets-encrypt",
			params: serverconfig.Params{
				Domain:            "example.com",
				EnableLetsEncrypt: true,
				HttpPort:          "8765",
			},
			want: "https://example.com",
		},
		{
			name: "domain with disable-https (reverse proxy scenario, fixes #2581)",
			params: serverconfig.Params{
				Domain:       "example.com",
				DisableHttps: true,
				HttpPort:     "8765",
			},
			want: "http://example.com:8765",
		},
		{
			name: "no domain, disable-https, server IP differs from 127.0.0.1",
			params: serverconfig.Params{
				DisableHttps: true,
				HttpPort:     "8765",
				ServerIp:     "192.168.1.100",
			},
			want: "http://192.168.1.100:8765, http://localhost:8765, http://127.0.0.1:8765",
		},
		{
			name: "no domain, disable-https, server IP is 127.0.0.1",
			params: serverconfig.Params{
				DisableHttps: true,
				HttpPort:     "8765",
				ServerIp:     "127.0.0.1",
			},
			want: "http://127.0.0.1:8765, http://localhost:8765",
		},
		{
			name: "no domain, https, server IP differs from 127.0.0.1",
			params: serverconfig.Params{
				HttpPort: "8765",
				ServerIp: "192.168.1.100",
			},
			want: "https://192.168.1.100:8765, https://localhost:8765, https://127.0.0.1:8765",
		},
		{
			name: "docker mode, disable-https",
			params: serverconfig.Params{
				DisableHttps: true,
				HttpPort:     "8765",
				Docker:       true,
			},
			want: "http://:8765",
		},
		{
			name: "docker mode, https",
			params: serverconfig.Params{
				HttpPort: "8765",
				Docker:   true,
			},
			want: "https://:8765",
		},
		{
			name: "invalid domain returns error",
			params: serverconfig.Params{
				Domain:   "not a valid domain!!!",
				HttpPort: "8765",
			},
			wantErr: true,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got, err := serverconfig.BuildServerName(tt.params)
			if (err != nil) != tt.wantErr {
				t.Errorf("BuildServerName() error = %v, wantErr %v", err, tt.wantErr)
				return
			}
			if got != tt.want {
				t.Errorf("BuildServerName() = %q, want %q", got, tt.want)
			}
		})
	}
}

// TestBuildServerNameDomainWithDisableHttps is the key regression test for issue #2581.
// Before the fix, --domain + --disable-https was rejected at the command level with
// "disabling HTTPS is not allowed when specifying a domain".
// After the fix, BuildServerName returns an http:// URL so Caddy accepts requests
// forwarded by a reverse proxy with the correct Host header.
func TestBuildServerNameDomainWithDisableHttps(t *testing.T) {
	params := serverconfig.Params{
		Domain:       "solidinvoice.example.com",
		DisableHttps: true,
		HttpPort:     "8765",
	}

	got, err := serverconfig.BuildServerName(params)
	if err != nil {
		t.Fatalf("BuildServerName() must not error for domain + disable-https, got: %v", err)
	}
	if !strings.HasPrefix(got, "http://") {
		t.Errorf("expected http:// scheme for --domain + --disable-https, got: %s", got)
	}
	if strings.Contains(got, "https://") {
		t.Errorf("unexpected https:// in server name for --domain + --disable-https, got: %s", got)
	}
	if !strings.Contains(got, "solidinvoice.example.com") {
		t.Errorf("expected domain in server name, got: %s", got)
	}
}
