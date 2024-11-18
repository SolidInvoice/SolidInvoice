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

use DateTime;
use DateTimeInterface;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\InstallBundle\Exception\ApplicationInstalledException;
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
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Contracts\Service\ResetInterface;
use function array_combine;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_search;
use function assert;
use function in_array;
use function Symfony\Component\String\u;

#[AsCommand(name: 'app:install', description: 'Installs the application')]
class InstallCommand extends Command
{
    /**
     * @param ServiceLocator<InstallationStepInterface> $installationSteps
     */
    public function __construct(
        private readonly ConfigWriter $configWriter,
        private readonly ManagerRegistry $registry,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        #[AutowireLocator(services: InstallationStepInterface::DI_TAG, defaultIndexMethod: 'getLabel', defaultPriorityMethod: 'priority')]
        private readonly ServiceLocator $installationSteps,
        private readonly KernelInterface $kernel,
        private readonly string $projectDir,
        private readonly ?string $installed
    ) {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return null === $this->installed || '' === $this->installed;
    }

    protected function configure(): void
    {
        $this->addOption('database-driver', null, InputOption::VALUE_REQUIRED, 'The database driver to use')
            ->addOption('database-host', null, InputOption::VALUE_REQUIRED, 'The database host')
            ->addOption('database-port', null, InputOption::VALUE_REQUIRED, 'The database port')
            ->addOption('database-name', null, InputOption::VALUE_REQUIRED, 'The name of the database to use (will be created if it doesn\'t exist)')
            ->addOption('database-user', null, InputOption::VALUE_REQUIRED, 'The name of the database user')
            ->addOption('database-password', null, InputOption::VALUE_REQUIRED, 'The password for the database user')
            ->addOption('skip-user', null, InputOption::VALUE_NONE, 'Skip creating the admin user')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'The password of admin user')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'The email address of admin user')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The locale to use');
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

        $success = (new FormatterHelper())->formatBlock('Application installed successfully!', 'bg=green;options=bold', true);
        $output->writeln('');
        $output->writeln($success);
        $output->writeln('');
        $output->writeln('As a final step, you must add a scheduled task to run every minute.');
        $output->writeln('');
        $output->writeln('Add the following cron job:');
        $output->writeln('');
        $output->writeln(sprintf('<comment>* * * * * php %s/console cron:run -e prod -n</comment>', $this->projectDir));

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function validate(InputInterface $input): self
    {
        $values = ['database-host', 'database-user', 'locale'];

        if (! $input->getOption('skip-user')) {
            $values = [...$values, 'admin-password', 'admin-email'];
        }

        foreach ($values as $option) {
            if (null === $input->getOption($option)) {
                throw new RuntimeException(sprintf('The --%s option needs to be specified', $option));
            }
        }
        if (! array_key_exists($locale = $input->getOption('locale'), Locales::getNames())) {
            throw new InvalidArgumentException(sprintf('The locale "%s" is invalid', $locale));
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function install(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->installationSteps as $step) {
            $output->writeln(sprintf('<info>Running step: %s</info>', $step->getLabel()));
            $step->execute(function (string $content) use ($output): void {
                $output->writeln($content, OutputInterface::VERBOSITY_VERBOSE);
            });
        }

        if (! $input->getOption('skip-user')) {
            $this->createAdminUser($input, $output);
        }

        $version = SolidInvoiceCoreBundle::VERSION;
        $entityManager = $this->registry->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository(Version::class);
        $repository->updateVersion($version);
        $time = new DateTime('NOW');
        $config = ['installed' => $time->format(DateTimeInterface::ATOM)];
        $this->configWriter->save($config);
    }

    private function createAdminUser(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('<info>Creating Admin User</info>');
        /** @var UserRepository $userRepository */
        $userRepository = $this->registry->getRepository(User::class);
        $email = $input->getOption('admin-email');

        try {
            $userRepository->loadUserByIdentifier($email);
        } catch (UserNotFoundException) {
            $output->writeln(sprintf('<comment>User %s already exists, skipping creation</comment>', $email));

            return;
        }

        $user = new User();
        $user->setEmail($input->getOption('admin-email'))
            ->setPassword($this->userPasswordHasher->hashPassword($user, $input->getOption('admin-password')))
            ->setEnabled(true);

        $em = $this->registry->getManagerForClass(User::class);

        if (! $em instanceof ObjectManager) {
            throw new RuntimeException(sprintf('No object manager found for class "%s".', User::class));
        }

        $em->persist($user);
        $em->flush();
    }

    /**
     * @throws EnvironmentIsBrokenException
     */
    private function saveConfig(InputInterface $input): self
    {
        // Don't update installed here, in case something goes wrong with the rest of the installation process
        $config = [
            'database_driver' => $input->getOption('database-driver'),
            'database_host' => $input->getOption('database-host'),
            'database_port' => $input->getOption('database-port'),
            'database_name' => $input->getOption('database-name'),
            'database_user' => $input->getOption('database-user'),
            'database_password' => $input->getOption('database-password'),
            'locale' => $input->getOption('locale'),
            'app_secret' => Key::createNewRandomKey()->saveToAsciiSafeString(),
        ];

        try {
            $nativeConnection = DriverManager::getConnection([
                'host' => $config['database_host'] ?? null,
                'port' => $config['database_port'] ?? null,
                'name' => $config['database_name'] ?? null,
                'user' => $config['database_user'] ?? null,
                'password' => $config['database_password'] ?? null,
                'driver' => $config['database_driver'] ?? null,
            ])->getNativeConnection();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        assert($nativeConnection instanceof PDO);

        $config['database_version'] = $nativeConnection->getAttribute(PDO::ATTR_SERVER_VERSION);

        $this->configWriter->save($config);

        $container = $this->kernel->getContainer();

        if ($container instanceof ResetInterface) {
            $container->reset();
            $container->set('kernel', $this->kernel);
        }

        return $this;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $availablePdoDrivers = array_values(array_intersect(
            array_map(static fn (string $driver) => "pdo_{$driver}", PDO::getAvailableDrivers()),
            DriverManager::getAvailableDrivers()
        ));

        // We can't support sqlite at the moment, since it requires a physical file
        if (in_array('pdo_sqlite', $availablePdoDrivers, true)) {
            unset($availablePdoDrivers[array_search('pdo_sqlite', $availablePdoDrivers, true)]);
        }

        $drivers = array_combine(
            array_map(static fn (string $driver) => u($driver)->replace('pdo_', '')->title()->toString(), $availablePdoDrivers),
            $availablePdoDrivers,
        );

        $options = [
            'database-driver' => (new ChoiceQuestion('<question>please enter your database type:</question> ', array_keys($drivers))),
            'database-host' => new Question('<question>please enter your database host:</question> '),
            'database-port' => new Question('<question>please enter your database port:</question> '),
            'database-name' => new Question('<question>please enter your database name:</question> '),
            'database-user' => new Question('<question>please enter your database username:</question> '),
            'database-password' => new Question('<question>please enter your database password:</question> '),
            'locale' => (new Question('<question>Please enter a locale:</question> '))
                ->setAutocompleterValues(array_keys(Locales::getNames())),
        ];

        if (! $input->getOption('skip-user')) {
            $passwordQuestion = new Question('<question>Please enter a password for the admin account:</question> ');
            $passwordQuestion->setHidden(true);

            $options['admin-email'] = new Question('<question>Please enter an email address for the admin account:</question> ');
            $options['admin-password'] = $passwordQuestion;
        }

        /** @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');

        foreach ($options as $option => $question) {
            if (null === $input->getOption($option)) {
                $value = $dialog->ask($input, $output, $question);

                if ($option === 'database-driver') {
                    $value = $drivers[$value];
                }

                $input->setOption($option, $value);
            }
        }
    }
}
