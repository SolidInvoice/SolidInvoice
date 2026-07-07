<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Command;

use Carbon\Carbon;
use DateTimeInterface;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Generator;
use InvalidArgumentException;
use Override;
use PDO;
use RuntimeException;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidInvoice\InstallBundle\Config\DatabaseConfig;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Exception\ApplicationInstalledException;
use SolidInvoice\InstallBundle\Step\CreateUserStep;
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Service\ResetInterface;
use function array_combine;
use function array_intersect;
use function array_keys;
use function array_map;
use function assert;
use function dirname;
use function in_array;
use function Symfony\Component\String\u;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Command\InstallCommandTest
 */
#[AsCommand(name: 'solidinvoice:install', description: 'Installs the application')]
class InstallCommand extends Command
{
    /**
     * @param ServiceLocator<InstallationStepInterface> $installationSteps
     */
    public function __construct(
        private readonly ConfigWriter $configWriter,
        private readonly ManagerRegistry $registry,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        #[AutowireLocator(InstallationStepInterface::DI_TAG)]
        private readonly ServiceLocator $installationSteps,
        private readonly KernelInterface $kernel,
        private readonly Telemetry $telemetry,
        #[Autowire(env: 'SOLIDINVOICE_CONFIG_DIR')]
        private readonly string $configDir,
        private readonly ?string $installed
    ) {
        parent::__construct();
    }

    #[Override]
    public function isEnabled(): bool
    {
        return null === $this->installed || '' === $this->installed;
    }

    protected function configure(): void
    {
        $this->addOption('database-driver', null, InputOption::VALUE_REQUIRED, 'The database driver to use')
            ->addOption('database-host', null, InputOption::VALUE_REQUIRED, 'The database host')
            ->addOption('database-port', null, InputOption::VALUE_REQUIRED, 'The database port')
            ->addOption('database-name', null, InputOption::VALUE_REQUIRED, "The name of the database to use (will be created if it doesn't exist)")
            ->addOption('database-user', null, InputOption::VALUE_REQUIRED, 'The name of the database user')
            ->addOption('database-password', null, InputOption::VALUE_REQUIRED, 'The password for the database user')
            ->addOption('skip-user', null, InputOption::VALUE_NONE, 'Skip creating the admin user')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'The password of admin user')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'The email address of admin user')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The locale to use')
            ->addOption('application-url', null, InputOption::VALUE_REQUIRED, 'The URL where this SolidInvoice instance will be accessible (including protocol, e.g. https://invoices.example.com). Use `bin/console secrets:set SOLIDINVOICE_APPLICATION_URL` to update this after installation.')
            ->addOption('disable-telemetry', null, InputOption::VALUE_NONE, 'Disable sending anonymous usage statistics');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->installed) {
            throw new ApplicationInstalledException();
        }

        $this->validate($input)
            ->saveConfig($input)
            ->install($input, $output);

        if (! $input->getOption('disable-telemetry')) {
            $this->telemetry->event(TelemetryEvent::InstallCompleted, ['method' => 'cli'], true);
        }

        $success = new FormatterHelper()
            ->formatBlock('Application installed successfully!', 'bg=green;options=bold', true);
        $output->writeln('');
        $output->writeln($success);
        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function validate(InputInterface $input): self
    {
        $values = ['database-driver', 'locale'];

        if (! $this->isSqlite($input)) {
            $values = ['database-host', 'database-user', 'database-name', ...$values];
        }

        if (! $input->getOption('skip-user')) {
            $values = [...$values, 'admin-password', 'admin-email'];
        }

        foreach ($values as $option) {
            if (null === $input->getOption($option)) {
                throw new RuntimeException(sprintf('The --%s option needs to be specified', $option));
            }
        }

        if (! array_key_exists((string) $locale = $input->getOption('locale'), Locales::getNames())) {
            throw new InvalidArgumentException(sprintf('The locale "%s" is invalid', $locale));
        }

        // The application URL is optional (it can be set later via
        // `secrets:set SOLIDINVOICE_APPLICATION_URL`), but validate it when provided.
        $applicationUrl = $input->getOption('application-url');

        if (null !== $applicationUrl && '' !== $applicationUrl) {
            $scheme = parse_url((string) $applicationUrl, PHP_URL_SCHEME);

            if (! in_array($scheme, ['http', 'https'], true) || filter_var($applicationUrl, FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException(sprintf('The application URL "%s" is not a valid URL. It must include a protocol (http:// or https://).', $applicationUrl));
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function install(InputInterface $input, OutputInterface $output): void
    {
        $progress = static function (string $content) use ($output): Generator {
            $output->writeln($content, OutputInterface::VERBOSITY_VERBOSE);

            yield;
        };

        foreach ($this->installationSteps as $step) {
            // The CLI creates the admin user from the command options (see
            // createAdminUser() below), which also re-enables disabled users, so
            // the form-data based user step is skipped here.
            if ($step instanceof CreateUserStep) {
                continue;
            }

            $output->writeln(sprintf('<info>Running step: %s</info>', $step->getLabel()));

            // execute() returns a Generator; it must be iterated for the step body to run.
            iterator_to_array($step->execute(new Installation(), $progress), false);
        }

        if (! $input->getOption('skip-user')) {
            $this->createAdminUser($input, $output);
        }

        $version = SolidInvoiceCoreBundle::VERSION;
        $entityManager = $this->registry->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository(Version::class);
        $repository->updateVersion($version);

        $time = Carbon::parse('NOW');
        $config = ['installed' => $time->format(DateTimeInterface::ATOM)];
        $this->configWriter->save($config);
    }

    private function createAdminUser(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('<info>Creating Admin User</info>');
        /** @var UserRepository $userRepository */
        $userRepository = $this->registry->getRepository(User::class);
        $email = $input->getOption('admin-email');

        $existingUser = $userRepository->findOneBy(['email' => $email]);

        $em = $this->registry->getManagerForClass(User::class);

        if (! $em instanceof ObjectManager) {
            throw new RuntimeException(sprintf('No object manager found for class "%s".', User::class));
        }

        if ($existingUser !== null) {
            if ($existingUser->isEnabled()) {
                $output->writeln(sprintf('<comment>User %s already exists, skipping creation</comment>', $email));

                return;
            }

            // Re-enable disabled user and update password
            $output->writeln(sprintf('<comment>Re-enabling disabled user (%s), and resetting password</comment>', $email));
            $existingUser->setPassword($this->userPasswordHasher->hashPassword($existingUser, $input->getOption('admin-password')))
                ->setEnabled(true)
                ->setVerified(true);

            $em->flush();

            return;
        }

        // Create new user
        $user = new User();
        $user->setEmail($email)
            ->setPassword($this->userPasswordHasher->hashPassword($user, $input->getOption('admin-password')))
            ->setEnabled(true)
            ->setVerified(true);

        $em->persist($user);
        $em->flush();
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    private function saveConfig(InputInterface $input): self
    {
        // Don't update installed here, in case something goes wrong with the rest of the installation process
        $driver = (string) $input->getOption('database-driver');

        $params = [
            // The scheme (e.g. mysql, pgsql, sqlite) is derived from the PDO
            // driver name so it maps onto DatabaseConfig's accepted schemes.
            'driver' => u($driver)->trimPrefix('pdo_')->toString(),
            'host' => $input->getOption('database-host'),
            'port' => $input->getOption('database-port'),
            'name' => $input->getOption('database-name'),
            'user' => $input->getOption('database-user'),
            'password' => $input->getOption('database-password'),
        ];

        if ($this->isSqlite($input)) {
            $params['name'] = $this->resolveSqlitePath($input);

            // CreateDatabaseStep opens the connection to create the SQLite file,
            // but not any missing parent directories, so make sure they exist.
            new Filesystem()->mkdir(dirname($params['name']));
        } else {
            $params['version'] = $this->fetchDatabaseVersion($driver, $input);
        }

        $config = [
            'database_url' => DatabaseConfig::paramsToDatabaseUrl($params),
            'locale' => $input->getOption('locale'),
            'enable_telemetry' => $input->getOption('disable-telemetry') ? '0' : '1',
            'app_secret' => Key::createNewRandomKey()->saveToAsciiSafeString(),
        ];

        // Only persist the application URL when provided; otherwise the
        // SOLIDINVOICE_APPLICATION_URL env default (empty) applies.
        $applicationUrl = $input->getOption('application-url');

        if (null !== $applicationUrl && '' !== $applicationUrl) {
            $config['application_url'] = $applicationUrl;
        }

        $this->configWriter->save($config);

        $container = $this->kernel->getContainer();

        if ($container instanceof ResetInterface) {
            $container->reset();
            $container->set('kernel', $this->kernel);
        }

        return $this;
    }

    private function fetchDatabaseVersion(string $driver, InputInterface $input): string
    {
        try {
            // The database name is intentionally omitted: the target database
            // may not exist yet (CreateDatabaseStep creates it later), so we
            // connect to the server only to read its version.
            $nativeConnection = DriverManager::getConnection([
                'driver' => $driver,
                'host' => $input->getOption('database-host'),
                'port' => $input->getOption('database-port'),
                'user' => $input->getOption('database-user'),
                'password' => $input->getOption('database-password'),
            ])->getNativeConnection();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        assert($nativeConnection instanceof PDO);

        return (string) $nativeConnection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    private function isSqlite(InputInterface $input): bool
    {
        return $input->getOption('database-driver') === 'pdo_sqlite';
    }

    private function defaultSqlitePath(): string
    {
        return $this->configDir . '/db/solidinvoice.db';
    }

    private function resolveSqlitePath(InputInterface $input): string
    {
        $path = $input->getOption('database-name');

        return null === $path || '' === $path
            ? $this->defaultSqlitePath()
            : (string) $path;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $availablePdoDrivers = array_values(array_intersect(
            array_map(static fn (string $driver) => 'pdo_' . $driver, PDO::getAvailableDrivers()),
            DriverManager::getAvailableDrivers()
        ));

        $drivers = array_combine(
            array_map(static fn (string $driver) => u($driver)->replace('pdo_', '')->title()->toString(), $availablePdoDrivers),
            $availablePdoDrivers,
        );

        /** @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');

        // Resolve the driver first, so we know whether to ask for server
        // connection details or a SQLite database file path.
        if (null === $input->getOption('database-driver')) {
            $driver = $dialog->ask(
                $input,
                $output,
                new ChoiceQuestion('<question>please enter your database type:</question> ', array_keys($drivers))
            );

            $input->setOption('database-driver', $drivers[$driver]);
        }

        if ($this->isSqlite($input)) {
            $options = [
                'database-name' => new Question(
                    sprintf('<question>please enter the path to the SQLite database file [%s]:</question> ', $this->defaultSqlitePath()),
                    $this->defaultSqlitePath()
                ),
            ];
        } else {
            $options = [
                'database-host' => new Question('<question>please enter your database host:</question> '),
                'database-port' => new Question('<question>please enter your database port:</question> '),
                'database-name' => new Question('<question>please enter your database name:</question> '),
                'database-user' => new Question('<question>please enter your database username:</question> '),
                'database-password' => new Question('<question>please enter your database password:</question> '),
            ];
        }

        $options['locale'] = new Question('<question>Please enter a locale:</question> ')
            ->setAutocompleterValues(array_keys(Locales::getNames()));
        $options['application-url'] = new Question('<question>Please enter the application URL (optional, e.g. https://invoices.example.com):</question> ');

        if (! $input->getOption('skip-user')) {
            $passwordQuestion = new Question('<question>Please enter a password for the admin account:</question> ');
            $passwordQuestion->setHidden(true);

            $options['admin-email'] = new Question('<question>Please enter an email address for the admin account:</question> ');
            $options['admin-password'] = $passwordQuestion;
        }

        foreach ($options as $option => $question) {
            if (null === $input->getOption($option)) {
                $input->setOption($option, $dialog->ask($input, $output, $question));
            }
        }
    }
}
