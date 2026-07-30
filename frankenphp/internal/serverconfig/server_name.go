package serverconfig

import (
	"fmt"

	"github.com/go-playground/validator/v10"
)

// Params holds the inputs needed to construct the Caddy SERVER_NAME value.
type Params struct {
	Domain            string
	DisableHttps      bool
	EnableLetsEncrypt bool
	HttpPort          string
	ServerIp          string
	Docker            bool
}

// BuildServerName returns the Caddy SERVER_NAME string for the given parameters.
//
// When a reverse proxy sits in front of SolidInvoice and forwards requests
// with a Host header that differs from the machine's IP address, Caddy must
// be told about that hostname so it can match incoming requests correctly.
// Use Domain + DisableHttps together to tell Caddy to accept requests for the
// given hostname over plain HTTP (the reverse proxy handles TLS):
//
//	solidinvoice run --domain solidinvoice.example.com --disable-https
func BuildServerName(p Params) (string, error) {
	if p.Domain != "" {
		validate := validator.New(validator.WithRequiredStructEnabled())
		if errs := validate.Var(p.Domain, "required,hostname"); errs != nil {
			return "", errs
		}

		if p.DisableHttps {
			return fmt.Sprintf("http://%s:%s", p.Domain, p.HttpPort), nil
		}
		if p.EnableLetsEncrypt {
			return fmt.Sprintf("https://%s", p.Domain), nil
		}
		return fmt.Sprintf("https://%s:%s", p.Domain, p.HttpPort), nil
	}

	protocol := "https"
	if p.DisableHttps {
		protocol = "http"
	}

	if p.Docker {
		return fmt.Sprintf("%s://:%s", protocol, p.HttpPort), nil
	}

	serverName := fmt.Sprintf("%s://%s:%s, %s://localhost:%s",
		protocol, p.ServerIp, p.HttpPort, protocol, p.HttpPort)
	if p.ServerIp != "127.0.0.1" {
		serverName += fmt.Sprintf(", %s://127.0.0.1:%s", protocol, p.HttpPort)
	}

	return serverName, nil
}
