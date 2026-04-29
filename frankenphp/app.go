package main

import (
	"bufio"
	"bytes"
	"compress/gzip"
	"context"
	_ "embed"
	"errors"
	"fmt"
	"io"
	"net"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"syscall"
	"time"

	"github.com/caddyserver/caddy/v2"
	caddycmd "github.com/caddyserver/caddy/v2/cmd"
	"github.com/caddyserver/caddy/v2/notify"
	"github.com/caddyserver/certmagic"
	"github.com/charmbracelet/lipgloss"
	"github.com/common-nighthawk/go-figure"
	"github.com/dunglas/frankenphp"
	"github.com/go-playground/validator/v10"
	"github.com/luno/jettison/log"
	"github.com/luno/lu"
	"github.com/luno/lu/process"

	"github.com/spf13/cobra"

	// plug in Caddy modules here.
	_ "github.com/caddyserver/caddy/v2/modules/standard"
	_ "github.com/dunglas/caddy-cbrotli"
	_ "github.com/dunglas/frankenphp/caddy"
	// _ "github.com/dunglas/mercure/caddy"
	// _ "github.com/dunglas/vulcain/caddy"
)

const appName = "SolidInvoice"
const appDescription = "Simple and elegant invoicing solution"
const defaultPort = "8765"

var rootCmd = &cobra.Command{
	Use: appName,
}

//go:embed app.tar.gz
var embeddedApp []byte

//go:embed app_checksum.txt
var embeddedAppChecksum []byte

type runningProcess struct {
	cmd    *exec.Cmd
	stderr *os.File
	stdout *os.File
}

var caddyExtraOptions = `
tls internal
`

var defaultServerIp string

var domain string
var httpPort = defaultPort
var serverIp string
var disableHttps bool
var enableLetsEncrypt bool
var skipIntro bool
var sslCertFile string
var sslKeyFile string
var messengerWorkers int
var enableWorkerMode bool
var workerThreads int
var logFormat string
var workerCount int
var enableMetrics bool
var metricsPort int

func main() {
	// Initialize app
	if err := initializeApp(); err != nil {
		fmt.Fprintf(os.Stderr, "Fatal error: %v\n", err)
		os.Exit(1)
	}

    setupCommands()

	// Run CLI
	if err := rootCmd.Execute(); err != nil {
		errStyle := lipgloss.NewStyle().Foreground(lipgloss.Color("9"))
		fmt.Println(errStyle.Render(err.Error()))
		os.Exit(1)
	}

	os.Exit(0)
}

func initializeApp() error {
	if len(embeddedApp) == 0 || len(embeddedAppChecksum) == 0 {
		return fmt.Errorf("embedded application missing - binary may be corrupted")
	}

	configDir, err := os.UserConfigDir()
	if err != nil {
		return fmt.Errorf("cannot access config directory: %w", err)
	}

	appDir, err := os.UserHomeDir()
	if err != nil {
		return fmt.Errorf("cannot access home directory: %w", err)
	}

	appPath, err := extractEmbeddedApp(appDir)
	if err != nil {
		return fmt.Errorf("failed to extract application: %w", err)
	}

	if err := os.Chdir(appPath); err != nil {
		return fmt.Errorf("cannot access application directory: %w", err)
	}

	upperAppName := strings.ToUpper(appName)
	defaultServerIp = getOutboundIP().String()

	// Set environment variables
	envVars := map[string]string{
		upperAppName + "_CONFIG_DIR": filepath.Join(configDir, appName),
		upperAppName + "_ENV":        "prod",
		upperAppName + "_DEBUG":      "0",
		"APP_PATH":                   appPath,
		"SOLIDINVOICE_RUNTIME":       "frankenphp",
	}

	// Only set if not already set
    for key, value := range envVars {
        if os.Getenv(key) == "" {
            if err := os.Setenv(key, value); err != nil {
                return fmt.Errorf("cannot set environment: %w", err)
            }
        }
    }

	return nil
}

func setupCommands() {
	serverCmd := &cobra.Command{
		Use:    "server",
		Short:  "Manages the application server",
		Hidden: true,
	}
	serverCmd.AddCommand(&cobra.Command{
		Use:   "start",
		Short: "Start the application server",
		RunE: func(cmd *cobra.Command, args []string) error {
			caddy.TrapSignals()
			appPath := os.Getenv("APP_PATH")
			if _, err := os.Stat(filepath.Join(appPath, "php.ini")); err == nil {
				iniScanDir := os.Getenv("PHP_INI_SCAN_DIR")

				if err := os.Setenv("PHP_INI_SCAN_DIR", iniScanDir+":"+appPath); err != nil {
					return err
				}
			}

			config, _, _, err := caddycmd.LoadConfig(filepath.Join(appPath, "Caddyfile"), "")
			if err != nil {
				return err
			}

			if err = caddy.Load(config, true); err != nil {
				return err
			}

			select {}
		},
	})

	rootCmd.AddCommand(serverCmd)

	/*serviceCmd := &cobra.Command{
		Use:   "service",
		Short: "Manages the application service",
	}
	serviceCmd.AddCommand(&cobra.Command{
		Use:   "install",
		Short: "Install the application as a background service",
		Run: func(cmd *cobra.Command, args []string) {
		},
	})

	rootCmd.AddCommand(serviceCmd)*/

	runCmd := &cobra.Command{
		Use:   "run",
		Short: "Runs " + appName,
		RunE: func(cmd *cobra.Command, args []string) error {
			// Set log format early (before any logging)
			if logFormat != "" {
				must(os.Setenv("LOG_FORMAT", logFormat))
			}

			caddyLogInfo := `
{
  "admin": { "disabled": true },
  "logging": {
    "logs": {
      "default": {
        "level": "INFO",
        "writer": { "output": "stdout" },
        "encoder": { "format": "` + logFormat + `" }
      }
    }
  }
}
`

			if err := caddy.Load([]byte(caddyLogInfo), true); err != nil {
				return fmt.Errorf("Failed to configure logging: %v\n", err)
			}

			// Set up logger to use Caddy's logger (even before Caddy fully loads)
			// This ensures all logs go through Caddy's logging system
			log.SetLogger(logger{})

			// Validate messenger workers count
			if messengerWorkers < 0 {
				return errors.New("messenger-workers must be 0 or greater (got " + fmt.Sprintf("%d", messengerWorkers) + ")")
			}

			listenPort := getAvailablePort(httpPort)

			if listenPort != httpPort {
				return errors.New("port " + httpPort + " is not available")
			}

			// Validate SSL configuration
			if enableLetsEncrypt && domain == "" {
				return fmt.Errorf("--lets-encrypt requires --domain")
			}

			// Validate custom SSL certificate
			if sslCertFile != "" || sslKeyFile != "" {
				if sslCertFile == "" || sslKeyFile == "" {
					return fmt.Errorf("--ssl-cert and --ssl-key must be used together")
				}
				if domain == "" {
					return fmt.Errorf("--ssl-cert requires --domain")
				}
				if enableLetsEncrypt {
					return fmt.Errorf("--ssl-cert cannot be used with --lets-encrypt")
				}

				// Validate certificate file exists
				if _, err := os.Stat(sslCertFile); err != nil {
					return fmt.Errorf("SSL certificate file not found: %s", sslCertFile)
				}

				// Validate key file exists
				if _, err := os.Stat(sslKeyFile); err != nil {
					return fmt.Errorf("SSL key file not found: %s", sslKeyFile)
				}
			}

			// When HTTPS is disabled, the app is running behind a reverse proxy.
			// Configure Symfony to trust proxy headers so it sees the original protocol.
			if disableHttps && os.Getenv("SYMFONY_TRUSTED_PROXIES") == "" {
				must(os.Setenv("SYMFONY_TRUSTED_PROXIES", "PRIVATE_SUBNETS,REMOTE_ADDR"))
			}

			// Configure SSL strategy
			var autoHttps string
			var tlsDirective string
			if disableHttps {
				autoHttps = "off"
			} else if enableLetsEncrypt {
				autoHttps = "on" // Enable Let's Encrypt
			} else if sslCertFile != "" && sslKeyFile != "" {
				autoHttps = "disable_redirects" // Custom certificate
				tlsDirective = fmt.Sprintf("tls %s %s", sslCertFile, sslKeyFile)
			} else {
				autoHttps = "disable_redirects" // Self-signed
				tlsDirective = caddyExtraOptions
			}

			// Build server name
			var serverName string
			if domain != "" {
				if disableHttps {
					return errors.New("disabling HTTPS is not allowed when specifying a domain")
				}

				validate := validator.New(validator.WithRequiredStructEnabled())
				if errs := validate.Var(domain, "required,hostname"); errs != nil {
					return errs
				}

				if enableLetsEncrypt {
					serverName = fmt.Sprintf("https://%s", domain)
				} else {
					serverName = fmt.Sprintf("https://%s:%s", domain, httpPort)
				}
			} else {
				protocol := "https"
				if disableHttps {
					protocol = "http"
				}

				if os.Getenv("SOLIDINVOICE_DOCKER") == "true" {
					serverName = fmt.Sprintf("%s://:%s", protocol, httpPort)
				} else {
					serverName = fmt.Sprintf("%s://%s:%s, %s://localhost:%s",
						protocol, serverIp, httpPort, protocol, httpPort)
					if serverIp != "127.0.0.1" {
						serverName += fmt.Sprintf(", %s://127.0.0.1:%s", protocol, httpPort)
					}
				}
			}

			must(os.Setenv("SERVER_NAME", serverName))
			must(os.Setenv("AUTO_HTTPS", autoHttps))

			if tlsDirective != "" {
				must(os.Setenv("CADDY_SERVER_EXTRA_DIRECTIVES", tlsDirective))
			}

			if len(serverIp) > 0 && serverIp != defaultServerIp {
				must(os.Setenv("SERVER_IP", serverIp))
			}

			// Configure FrankenPHP worker mode
			// Check environment variable first, then CLI flag
			workerModeEnabled := os.Getenv("FRANKENPHP_WORKER_MODE") == "1" ||
				os.Getenv("SOLIDINVOICE_WORKER_MODE") == "1" ||
				enableWorkerMode

			if workerModeEnabled {
				log.Info(nil, "Worker mode requested - configuring...")

				// Validate worker threads
				if workerThreads < 1 {
					return fmt.Errorf("worker-threads must be at least 1 (got %d)", workerThreads)
				}
				if workerThreads > 256 {
					return fmt.Errorf("worker-threads cannot exceed 256 (got %d)", workerThreads)
				}

				appPath := os.Getenv("APP_PATH")
				workerScript := filepath.Join(appPath, "public", "index.php")

				log.Info(nil, fmt.Sprintf("Worker script path: %s", workerScript))

				// Check if worker script exists
				if _, err := os.Stat(workerScript); err != nil {
					return fmt.Errorf("worker script not found: %s (error: %v)", workerScript, err)
				}

				// Set Symfony runtime for FrankenPHP worker mode
				must(os.Setenv("APP_RUNTIME", "Runtime\\FrankenPhpSymfony\\Runtime"))

				// FrankenPHP worker syntax: worker <script> <num_threads>
				frankenphpConfig := fmt.Sprintf("worker %s %d", workerScript, workerThreads)
				must(os.Setenv("FRANKENPHP_CONFIG", frankenphpConfig))

				log.Info(nil, fmt.Sprintf("FrankenPHP worker mode enabled with %d threads", workerThreads))
				log.Info(nil, fmt.Sprintf("FRANKENPHP_CONFIG: %s", frankenphpConfig))
			} else {
				log.Info(nil, "Worker mode disabled (default)")
			}

			// Metrics configuration
			if enableMetrics {
				if metricsPort < 1 || metricsPort > 65535 {
					return fmt.Errorf("metrics port must be between 1 and 65535 (got %d)", metricsPort)
				}
				metricsListenPort := fmt.Sprintf("%d", metricsPort)
				if metricsListenPort == httpPort {
					return fmt.Errorf("metrics port %d cannot be the same as the HTTP port", metricsPort)
				}
				if !portAvailable(metricsListenPort) {
					return fmt.Errorf("metrics port %d is not available", metricsPort)
				}

				// Enable Caddy metrics collection via global options
				globalOpts := os.Getenv("CADDY_GLOBAL_OPTIONS")
				if !strings.Contains(globalOpts, "metrics") {
					if globalOpts != "" {
						globalOpts += "\n"
					}
					globalOpts += "metrics"
					must(os.Setenv("CADDY_GLOBAL_OPTIONS", globalOpts))
				}

				// Add dedicated metrics server block
				extraConfig := os.Getenv("CADDY_EXTRA_CONFIG")
				if extraConfig != "" {
					extraConfig += "\n"
				}
				extraConfig += fmt.Sprintf(":%d {\n\tmetrics /metrics\n}", metricsPort)
				must(os.Setenv("CADDY_EXTRA_CONFIG", extraConfig))

				log.Info(nil, fmt.Sprintf("Metrics enabled on port %d at /metrics", metricsPort))
			}

			app := lu.App{
				StartupTimeout:  time.Second * 10,
				ShutdownTimeout: time.Second * 10,
				UseProcessFile:  false,
			}

			//app.AddProcess(wrapInternalCmd("server", "start"))
			loop := process.Loop(func(ctx context.Context) error {
				// caddy.TrapSignals()
				appPath := os.Getenv("APP_PATH")
				if _, err := os.Stat(filepath.Join(appPath, "php.ini")); err == nil {
					iniScanDir := os.Getenv("PHP_INI_SCAN_DIR")

					if err := os.Setenv("PHP_INI_SCAN_DIR", iniScanDir+":"+appPath); err != nil {
						return err
					}
				}

				config, _, _, err := caddycmd.LoadConfig(filepath.Join(appPath, "Caddyfile"), "")
				if err != nil {
					return err
				}

				if err = caddy.Load(config, true); err != nil {
					return err
				}

				select {
				case <-ctx.Done():
					return ctx.Err()
				}
			})
			loop.Shutdown = func(ctx context.Context) error {
				if err := notify.Stopping(); err != nil {
					return err
				}
				log.Info(nil, "Shutting down Caddy server...")
				if err := caddy.Stop(); err != nil {
					return err
				}

				certmagic.CleanUpOwnLocks(ctx, caddy.Log())

				return nil
			}
			app.AddProcess(loop)

			// Spawn multiple messenger worker processes
			// Each worker runs independently and is automatically restarted by lu if it exits
			if messengerWorkers > 0 {
				log.Info(nil, "Starting "+fmt.Sprintf("%d", messengerWorkers)+" messenger worker(s)")
			}

			for i := 1; i <= messengerWorkers; i++ {
				messengerWorker := process.Loop(func(ctx context.Context) error {
					if !isAppInstalled() {
						// App not yet installed; wait before retrying so we don't tight-loop
						select {
						case <-ctx.Done():
							return ctx.Err()
						case <-time.After(30 * time.Second):
							return nil
						}
					}
					if err := runConsoleCommand("messenger:setup-transports"); err != nil {
						log.Error(ctx, errors.Join(errors.New("failed to setup messenger transports"), err))
					}
					return runConsoleCommand(
						"messenger:consume",
						"--limit",
						"100",
						"--time-limit",
						"3600",
						"--memory-limit",
						"128M",
						"--all",
					)
				})

				app.AddProcess(messengerWorker)
			}

			app.OnEvent = func(ctx context.Context, event lu.Event) {
				switch event.Type {
				case lu.AppStartup:
					// Clear cache on app start, to avoid issues with generated configs
					if err := runConsoleCommand("cache:clear"); err != nil {
						log.Error(ctx, errors.Join(errors.New("failed to clear cache"), err))
					}
					// Generate OAuth2 signing keys for the MCP server on first boot.
					// Idempotent — skipped if the keys already exist in SOLIDINVOICE_CONFIG_DIR/oauth/.
					if err := runConsoleCommand("mcp:keys:generate"); err != nil {
						log.Error(ctx, errors.Join(errors.New("failed to generate MCP OAuth signing keys"), err))
					}
				case lu.AppRunning:
					if !skipIntro {
						outputAppInfo()
					}
				case lu.AppTerminating:
					log.Info(nil, "Application is shutting down...")
					if err := runConsoleCommand("messenger:stop-workers"); err != nil {
						log.Error(ctx, errors.Join(errors.New("failed to stop messenger workers"), err))
					}
				default:
				}
			}

			app.Run()

			return nil
		},
	}
	rootCmd.AddCommand(runCmd)

	runCmd.PersistentFlags().StringVar(&domain, "domain", "", "The domain name to use for the application. When specifying a domain, an SSL certificate will automatically be generated for you")
	runCmd.PersistentFlags().StringVar(&httpPort, "port", defaultPort, "The default port to use for the application. When specifying a domain to use, the port will default to 443")
	runCmd.PersistentFlags().StringVar(&serverIp, "server-ip", defaultServerIp, "If you have multiple IP addresses on your server, specify the IP address to use. By default, the server will bind to all IP addresses")
	runCmd.PersistentFlags().BoolVar(&disableHttps, "disable-https", false, "Disable HTTPS. The application will only be accessible using http://. This setting is not recommended, unless you are setting up a reverse proxy which will use https")
	runCmd.PersistentFlags().BoolVar(&enableLetsEncrypt, "lets-encrypt", false, "Enable Let's Encrypt for automatic SSL certificates (requires --domain)")
	runCmd.PersistentFlags().StringVar(&sslCertFile, "ssl-cert", "", "Path to custom SSL certificate file (requires --ssl-key and --domain)")
	runCmd.PersistentFlags().StringVar(&sslKeyFile, "ssl-key", "", "Path to custom SSL private key file (requires --ssl-cert and --domain)")
	runCmd.PersistentFlags().BoolVar(&enableWorkerMode, "worker-mode", false, "Enable FrankenPHP worker mode for improved performance (keeps PHP workers alive between requests). Recommended for SaaS/high-traffic deployments. Can also be enabled via FRANKENPHP_WORKER_MODE=1 environment variable")
	runCmd.PersistentFlags().IntVar(&workerThreads, "worker-threads", 2, "Number of FrankenPHP worker threads when worker mode is enabled (default: 2)")
	runCmd.PersistentFlags().IntVar(&messengerWorkers, "messenger-workers", 1, "Number of messenger worker processes to spawn. Each worker processes async messages independently. Set to 0 to disable built-in workers entirely (recommended for Kubernetes, where a dedicated worker pod runs 'solidinvoice worker'). Increase above 1 for high-traffic standalone deployments (e.g., --messenger-workers=5)")
	runCmd.PersistentFlags().StringVar(&logFormat, "log-format", "console", "Log output format: 'json' for structured JSON logs, or 'console' (default) for human-readable console output")
	runCmd.PersistentFlags().BoolVar(&skipIntro, "skip-intro", false, "Skip the introductory application info message")
	runCmd.PersistentFlags().BoolVar(&enableMetrics, "enable-metrics", false, "Enable Prometheus metrics endpoint on a dedicated port. Exposes Caddy HTTP metrics and FrankenPHP worker/thread metrics for scraping")
	runCmd.PersistentFlags().IntVar(&metricsPort, "metrics-port", 9090, "Port for the Prometheus metrics endpoint (only used when --enable-metrics is set)")

	workerCmd := &cobra.Command{
		Use:   "worker",
		Short: "Run messenger worker processes",
		Long:  "Starts one or more Symfony Messenger consumer processes managed by a supervisor loop with automatic restart and graceful shutdown. Intended for use in dedicated worker containers (e.g. a Kubernetes worker Deployment). Install detection is built in — workers wait automatically until the application is installed.",
		RunE: func(cmd *cobra.Command, args []string) error {
			if logFormat != "" {
				must(os.Setenv("LOG_FORMAT", logFormat))
			}

			caddyLogInfo := `
{
  "admin": { "disabled": true },
  "logging": {
    "logs": {
      "default": {
        "level": "INFO",
        "writer": { "output": "stdout" },
        "encoder": { "format": "` + logFormat + `" }
      }
    }
  }
}
`
			if err := caddy.Load([]byte(caddyLogInfo), true); err != nil {
				return fmt.Errorf("failed to configure logging: %v", err)
			}

			log.SetLogger(logger{})

			if workerCount < 1 {
				return fmt.Errorf("workers must be at least 1 (got %d)", workerCount)
			}

			app := lu.App{
				StartupTimeout:  time.Second * 10,
				ShutdownTimeout: time.Second * 30,
				UseProcessFile:  false,
			}

			log.Info(nil, fmt.Sprintf("Starting %d messenger worker(s)", workerCount))

			for i := 1; i <= workerCount; i++ {
				worker := process.Loop(func(ctx context.Context) error {
					if !isAppInstalled() {
						select {
						case <-ctx.Done():
							return ctx.Err()
						case <-time.After(30 * time.Second):
							return nil
						}
					}
					if err := runConsoleCommand("messenger:setup-transports"); err != nil {
						log.Error(ctx, errors.Join(errors.New("failed to setup messenger transports"), err))
					}
					return runConsoleCommand(
						"messenger:consume",
						"--limit",
						"100",
						"--time-limit",
						"3600",
						"--memory-limit",
						"128M",
						"--all",
					)
				})
				app.AddProcess(worker)
			}

			app.OnEvent = func(ctx context.Context, event lu.Event) {
				switch event.Type {
				case lu.AppTerminating:
					log.Info(nil, "Worker is shutting down gracefully...")
					if err := runConsoleCommand("messenger:stop-workers"); err != nil {
						log.Error(ctx, errors.Join(errors.New("failed to stop messenger workers"), err))
					}
				}
			}

			app.Run()

			return nil
		},
	}

	rootCmd.AddCommand(workerCmd)

	workerCmd.PersistentFlags().IntVar(&workerCount, "workers", 1, "Number of messenger worker processes to spawn (default: 1). Each worker independently consumes async messages and is automatically restarted on failure.")
	workerCmd.PersistentFlags().StringVar(&logFormat, "log-format", "console", "Log output format: 'json' for structured JSON logs, or 'console' (default) for human-readable output")

	rootCmd.AddCommand(&cobra.Command{
		Use:                "version",
		Short:              "Display application version",
		DisableFlagParsing: true,
		RunE:               runCaddyCommand("version"),
	})
	rootCmd.AddCommand(&cobra.Command{
		Use:                "build-info",
		Short:              "Display application version",
		DisableFlagParsing: true,
		RunE:               runCaddyCommand("build-info"),
	})
	rootCmd.AddCommand(&cobra.Command{
		Use:                "console",
		Short:              "Run the embedded console commands",
		DisableFlagParsing: true,
		Run: func(cmd *cobra.Command, args []string) {
			appPath := os.Getenv("APP_PATH")
			frankenphp.ExecuteScriptCLI(appPath+"/bin/console", append([]string{"console"}, args...))
		},
	})
}

func wrapInternalCmd(args ...string) lu.Process {
	p := &runningProcess{
		stderr: os.Stderr,
		stdout: os.Stdout,
	}

	loop := process.Loop(func(ctx context.Context) error {
		return p.runInternalCommand(args...)
	})

	loop.Shutdown = func(ctx context.Context) error {
		if p.cmd != nil && p.cmd.Process != nil {
			return p.cmd.Process.Signal(syscall.SIGINT)
		}

		return nil
	}

	return loop
}

func getAvailablePort(defaultPort string) string {
	if !portAvailable(defaultPort) {
		listener, err := net.Listen("tcp", ":0")
		must(err)

		defer must(listener.Close())

		_, port, err := net.SplitHostPort(listener.Addr().String())
		return mustVal(port, err)
	}

	return defaultPort
}

func extractEmbeddedApp(appDir string) (string, error) {
	appPath := filepath.Join(appDir, "."+appName, "app_"+string(embeddedAppChecksum))

	if _, err := os.Stat(appPath); os.IsNotExist(err) {
		must(os.Setenv("COPYFILE_DISABLE", "1"))

		appTar, err := gUnzipData(embeddedApp)
		if err != nil {
			return "", err
		}

		if err = untar(appTar, appPath); err != nil {
			must(os.RemoveAll(appPath))
			return "", err
		}
	}
	return appPath, nil
}

func runCaddyCommand(command ...string) func(cmd *cobra.Command, args []string) error {
	return func(cmd *cobra.Command, args []string) error {
		originalArgs := os.Args

		defer (func() {
			os.Args = originalArgs
		})()

		os.Args = append([]string{appName}, command...)

		caddycmd.Main()

		return nil
	}
}

func portAvailable(port string) bool {
	ln, err := net.Listen("tcp", ":"+port)
	if err != nil {
		return false
	}
	must(ln.Close())

	return true
}

func (p *runningProcess) runInternalCommand(args ...string) error {
	binary, err := os.Executable()
	if err != nil {
		return err
	}

	p.cmd = exec.Command(binary, args...)
	p.cmd.SysProcAttr = &syscall.SysProcAttr{Setpgid: true}
	p.cmd.Env = os.Environ()

	if p.stderr != nil {
		p.cmd.Stderr = p.stderr
	} else {
		stderr, err := p.cmd.StderrPipe()
		if err != nil {
			return err
		}
		go tee(stderr)
	}

	if p.stdout != nil {
		p.cmd.Stdout = p.stdout
	} else {
		stdout, err := p.cmd.StdoutPipe()
		if err != nil {
			return err
		}
		go tee(stdout)
	}

	if err = p.cmd.Start(); err != nil {
		return err
	}

	return p.cmd.Wait()
}

func tee(r io.ReadCloser) {
	sc := bufio.NewScanner(r)
	for sc.Scan() {
		caddy.Log().Info(sc.Text())
	}
}

func runInternalCommand(args ...string) error {
	return (&runningProcess{}).runInternalCommand(args...)
}

func runConsoleCommand(args ...string) error {
	args = append([]string{"console"}, args...)
	args = append(args, "--no-ansi")
	args = append(args, "--no-interaction")

	return runInternalCommand(args...)
}

func isAppInstalled() bool {
	return runConsoleCommand("solidinvoice:is-installed") == nil
}

func must(err error) {
	if err != nil {
		panic(err)
	}
}

func mustVal[t any](val t, err error) t {
	must(err)

	return val
}

var (
	titleStyle   = lipgloss.NewStyle().Foreground(lipgloss.Color("#1CC129")).Bold(true)
	descStyle    = lipgloss.NewStyle().Italic(true)
	noteStyle    = lipgloss.NewStyle().Foreground(lipgloss.Color("226")).Bold(true)
	warningStyle = lipgloss.NewStyle().Foreground(lipgloss.Color("9")).Bold(true)
	linkStyle    = lipgloss.NewStyle().Foreground(lipgloss.Color("38")).Bold(true).Underline(true)
	borderStyle  = lipgloss.NewStyle().Border(lipgloss.ThickBorder(), true).Padding(1, 2)

	asciiAppName = figure.NewFigure(appName, "slant", true)
)

func getOutboundIP() net.IP {
	addrs, err := net.InterfaceAddrs()
	if err != nil {
		return []byte{}
	}
	for _, address := range addrs {
		// check the address type and if it is not a loopback the display it
		if ipnet, ok := address.(*net.IPNet); ok && !ipnet.IP.IsLoopback() {
			if ipnet.IP.To4() != nil {
				return ipnet.IP
			}
		}
	}
	return []byte{}
}

func outputAppInfo() {

	var urls string
	var domainNote string

	if len(domain) > 0 {
		domainNote = "\n\n" +
			noteStyle.Render("Note: ") +
			descStyle.Render("an SSL certificate will automatically be generated for you on the domain ") +
			noteStyle.Render(domain)
	}

	for _, name := range strings.Split(os.Getenv("SERVER_NAME"), ",") {
		urls += strings.TrimPrefix(name, " ") + "\n"
	}

	if disableHttps {
		domainNote += "\n\n" + warningStyle.Render("Warning: ") + descStyle.Render("HTTPS is disabled.")
	}

	var metricsNote string
	if enableMetrics {
		metricsNote = "\n\n" +
			noteStyle.Render("Metrics: ") +
			descStyle.Render("Prometheus metrics available at ") +
			linkStyle.Render(fmt.Sprintf("http://localhost:%d/metrics", metricsPort))
	}

	fmt.Println(borderStyle.Render(
		descStyle.Render("Welcome to") +
			"\n" +
			titleStyle.Render(asciiAppName.String()) +
			"\n\n" +
			descStyle.Italic(true).Render(appDescription) +
			"\n\n" +
			"Your application is running and available at the following URLs:\n" +
			descStyle.Italic(false).PaddingLeft(2).Render(linkStyle.Render(urls)) +
			domainNote +
			metricsNote,
	),
	)
}

func gUnzipData(data []byte) ([]byte, error) {
	b := bytes.NewBuffer(data)

	var r io.Reader
	r, err := gzip.NewReader(b)
	if err != nil {
		return nil, err
	}

	var resB bytes.Buffer
	_, err = resB.ReadFrom(r)
	if err != nil {
		return nil, err
	}

	return resB.Bytes(), nil
}
