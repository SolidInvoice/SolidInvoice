package main

import (
	"bufio"
	"context"
	_ "embed"
	"errors"
	"fmt"
	"github.com/caddyserver/caddy/v2"
	caddycmd "github.com/caddyserver/caddy/v2/cmd"
	"github.com/charmbracelet/lipgloss"
	"github.com/common-nighthawk/go-figure"
	"github.com/dunglas/frankenphp"
	"github.com/go-playground/validator/v10"
	"github.com/luno/jettison/log"
	"github.com/luno/lu"
	"github.com/luno/lu/process"
	"io"
	"net"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"syscall"
	"time"

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

//go:embed app.tar
var embeddedApp []byte

//go:embed app_checksum.txt
var embeddedAppChecksum []byte

type runningProcess struct {
	cmd *exec.Cmd
}

func init() {
	if len(embeddedApp) == 0 || len(embeddedAppChecksum) == 0 {
		panic("App initialization failed")
		return
	}
}

var caddyExtraOptions = `
tls internal
`
var caddyGlobalOptions = `
auto_https disable_redirects
`

var defaultServerIp string

var domain string
var httpPort = defaultPort
var serverIp string
var disableHttps bool

func main() {
	configDir := mustVal(os.UserConfigDir())
	appDir := mustVal(os.UserHomeDir())
	appPath := mustVal(extractEmbeddedApp(appDir))

	must(os.Chdir(appPath))

	upperAppName := strings.ToUpper(appName)

	defaultServerIp = getOutboundIP().String()

	must(os.Setenv(upperAppName+"_CONFIG_DIR", filepath.Join(configDir, appName)))
	must(os.Setenv(upperAppName+"_ENV", "prod"))
	must(os.Setenv(upperAppName+"_DEBUG", "0"))
	must(os.Setenv("APP_PATH", appPath))

	// os.Setenv("APP_RUNTIME", "Runtime\\FrankenPhpSymfony\\Runtime")

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
			if _, err := os.Stat(filepath.Join(appPath, "php.ini")); err == nil {
				iniScanDir := os.Getenv("PHP_INI_SCAN_DIR")

				if err := os.Setenv("PHP_INI_SCAN_DIR", iniScanDir+":"+appPath); err != nil {
					return err
				}
			}

			config, _, err := caddycmd.LoadConfig(filepath.Join(appPath, "Caddyfile"), "")
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

			listenPort := getAvailablePort(httpPort)

			if listenPort != httpPort {
				return errors.New("port " + httpPort + " is not available")
			}

			if len(domain) > 0 {

				if disableHttps == true {
					return errors.New("disabling HTTPS is not allowed when specifying a domain")
				}

				validate := validator.New(validator.WithRequiredStructEnabled())

				errs := validate.Var(domain, "required,hostname")

				if errs != nil {
					return errs
				}

				must(os.Setenv("SERVER_NAME", "https://"+domain+":"+httpPort))
				must(os.Setenv("AUTO_HTTPS", "disable_redirects"))
			} else {
				protocal := "https"
				if disableHttps == true {
					protocal = "http"
				}

				serverName := protocal + "://" + serverIp + ":" + httpPort + ", " + protocal + "://localhost:" + httpPort

				if serverIp != "127.0.0.1" {
					serverName += ", " + protocal + "://127.0.0.1:" + httpPort
				}

				must(os.Setenv("SERVER_NAME", serverName))

				if disableHttps {
					must(os.Setenv("AUTO_HTTPS", "off"))
				} else {
					must(os.Setenv("CADDY_SERVER_EXTRA_DIRECTIVES", caddyExtraOptions))
					must(os.Setenv("AUTO_HTTPS", "disable_redirects"))
				}
			}

			if len(serverIp) > 0 && serverIp != defaultServerIp {
				must(os.Setenv("SERVER_IP", serverIp))
			}

			log.SetLogger(logger{})

			app := lu.App{
				StartupTimeout:  time.Second * 10,
				ShutdownTimeout: time.Second * 10,
				UseProcessFile:  false,
			}

			messenger := process.Loop(func(ctx context.Context) error {
				return runConsoleCommand(
					"messenger:consume",
					"--all",
					"--limit",
					"50",
					"--time-limit",
					"3600",
				)
			})
			messenger.Shutdown = func(ctx context.Context) error {
				return runConsoleCommand("messenger:stop-workers")
			}

			app.AddProcess(wrapInternalCmd("server", "start"))
			app.AddProcess(messenger)
			app.AddProcess(process.Scheduled(
				func(role string) process.ContextFunc {
					return func(ctx context.Context) (context.Context, context.CancelFunc, error) {
						ctx, cancel := context.WithCancel(ctx)
						return ctx, cancel, nil
					}
				},
				new(memStore),
				appName+"_scheduled_cron",
				process.Every(time.Minute),
				func(ctx context.Context, lastRunTime, runTime time.Time, runID string) error {
					return runConsoleCommand("schedule:run")
				},
			))

			app.OnEvent = func(ctx context.Context, event lu.Event) {
				if event.Type == lu.AppRunning {
					time.Sleep(time.Second * 1) // Give enough time for all processes to start and output their logs
					outputAppInfo()
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

	rootCmd.AddCommand(&cobra.Command{
		Use:                "version",
		Short:              "Display application version",
		DisableFlagParsing: true,
		RunE:               runCaddyCommand("version"),
	})
	rootCmd.AddCommand(&cobra.Command{
		Use:                "console",
		Short:              "Run the embedded console commands",
		DisableFlagParsing: true,
		Run: func(cmd *cobra.Command, args []string) {
			frankenphp.ExecuteScriptCLI(appPath+"/bin/console", append([]string{"console"}, args...))
		},
	})

	err := rootCmd.Execute()

	if err != nil {
		errStyle := lipgloss.NewStyle().Foreground(lipgloss.Color("9"))
		fmt.Println(errStyle.Render(err.Error()))
		os.Exit(1)
	}

	os.Exit(0)
}

func wrapInternalCmd(args ...string) lu.Process {
	p := &runningProcess{}

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

		if err = untar(appPath); err != nil {
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

	stderr, err := p.cmd.StderrPipe()
	if err != nil {
		return err
	}
	stdout, err := p.cmd.StdoutPipe()
	if err != nil {
		return err
	}

	if err = p.cmd.Start(); err != nil {
		return err
	}

	go (func(output *io.ReadCloser) {
		scanner := bufio.NewScanner(stderr)
		scanner.Split(bufio.ScanLines)
		for scanner.Scan() {
			m := scanner.Text()
			caddy.Log().Info(m)
		}
	})(&stderr)

	go (func(output *io.ReadCloser) {
		scanner := bufio.NewScanner(stderr)
		scanner.Split(bufio.ScanLines)
		for scanner.Scan() {
			m := scanner.Text()
			caddy.Log().Info(m)
		}
	})(&stdout)

	return p.cmd.Wait()
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

	fmt.Println(borderStyle.Render(
		descStyle.Render("Welcome to") +
			"\n" +
			titleStyle.Render(asciiAppName.String()) +
			"\n\n" +
			descStyle.Italic(true).Render(appDescription) +
			"\n\n" +
			"Your application is running and available at the following URLs:\n" +
			descStyle.Italic(false).PaddingLeft(2).Render(linkStyle.Render(urls)) +
			domainNote,
	),
	)
}
