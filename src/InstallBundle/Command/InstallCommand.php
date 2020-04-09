<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Command;

use DateTime;
use Defuse\Crypto\Key;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use InvalidArgumentException;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\InstallBundle\Exception\ApplicationInstalledException;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class InstallCommand extends Command
{
    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string|null
     */
    private $installed;

    /**
     * @var Migration
     */
    private $migration;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    public function __construct(
        ConfigWriter $configWriter,
        Migration $migration,
        ManagerRegistry $registry,
        UserPasswordEncoderInterface $userPasswordEncoder,
        string $projectDir,
        ?string $installed
    ) {
        $this->configWriter = $configWriter;
        $this->projectDir = $projectDir;
        $this->installed = $installed;
        $this->migration = $migration;
        $this->registry = $registry;
        $this->userPasswordEncoder = $userPasswordEncoder;

        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return null === $this->installed;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:install')
            ->setDescription('Installs the application')
            ->addOption('database-driver', null, InputOption::VALUE_REQUIRED, 'The database driver to use', 'pdo_mysql')
            ->addOption('database-host', null, InputOption::VALUE_REQUIRED, 'The database host', 'localhost')
            ->addOption('database-port', null, InputOption::VALUE_REQUIRED, 'The database port', 3306)
            ->addOption('database-name', null, InputOption::VALUE_REQUIRED, 'The name of the database to use (will be created if it doesn\'t exist)', 'solidinvoice')
            ->addOption('database-user', null, InputOption::VALUE_REQUIRED, 'The name of the database user')
            ->addOption('database-password', null, InputOption::VALUE_REQUIRED, 'The password for the database user')
            ->addOption('mailer-transport', null, InputOption::VALUE_REQUIRED, 'The email transport to use (PHPMail, Sendmail, SMTP, Gmail)', 'mail')
            ->addOption('mailer-host', null, InputOption::VALUE_REQUIRED, 'The email host (only applicable for SMTP)', 'localhost')
            ->addOption('mailer-user', null, InputOption::VALUE_REQUIRED, 'The user for email authentication (only applicable for SMTP and Gmail)')
            ->addOption('mailer-password', null, InputOption::VALUE_REQUIRED, 'The password for the email user (only applicable for SMTP and Gmail)')
            ->addOption('mailer-port', null, InputOption::VALUE_REQUIRED, 'The email port to use  (only applicable for SMTP and Gmail)', 25)
            ->addOption('mailer-encryption', null, InputOption::VALUE_REQUIRED, 'The encryption to use for email, if any')
            ->addOption('admin-username', null, InputOption::VALUE_REQUIRED, 'The username of the admin user')
            ->addOption('admin-password', null, InputOption::VALUE_REQUIRED, 'The password of admin user')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'The email address of admin user')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The locale to use')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'The currency to use');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->validate($input)
            ->saveConfig($input)
            ->install($input, $output);

        $success = $this->getHelper('formatter')->formatBlock('Application installed successfully!', 'bg=green;options=bold', true);
        $output->writeln('');
        $output->writeln($success);
        $output->writeln('');
        $output->writeln('As a final step, you must add a scheduled task to run daily.');
        $output->writeln('You can choose what time the command should run, but 12AM is a good default if you are unsure.');
        $output->writeln('');
        $output->writeln('Add the following cron job to run daily at 12AM:');
        $output->writeln('');
        $output->writeln(sprintf('<comment>0 0 * * * php %s/console cron:run -e prod -n</comment>', $this->projectDir));

        return 0;
    }

    /**
     * @throws Exception
     */
    private function validate(InputInterface $input): self
    {
        $values = ['database-host', 'database-user', 'admin-username', 'admin-password', 'admin-email', 'locale', 'currency'];
        foreach ($values as $option) {
            if (null === $input->getOption($option)) {
                throw new Exception(sprintf('The --%s option needs to be specified', $option));
            }
        }
        $currencies = array_keys(Currencies::getNames());
        $locales = array_keys(Locales::getNames());
        if (!in_array($locale = $input->getOption('locale'), $locales, true)) {
            throw new InvalidArgumentException(sprintf('The locale "%s" is invalid', $locale));
        }

        if (!in_array($currency = $input->getOption('currency'), $currencies, true)) {
            throw new InvalidArgumentException(sprintf('The currency "%s" is invalid', $currency));
        }

        if ('smtp' === strtolower($input->getOption('mailer-transport'))) {
            if (null === $input->getOption('mailer-host')) {
                throw new \Exception('The --mailer-host option needs to be specified when using SMTP as email transport');
            }
            if (null === $input->getOption('mailer-port')) {
                throw new \Exception('The --mailer-port option needs to be specified when using SMTP as email transport');
            }
        } elseif ('gmail' === strtolower($input->getOption('mailer-transport'))) {
            if (null === $input->getOption('mailer-user')) {
                throw new \Exception('The --mailer-user option needs to be specified when using Gmail as email transport');
            }
            if (null === $input->getOption('mailer-password')) {
                throw new \Exception('The --mailer-password option needs to be specified when using Gmail as email transport');
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function install(InputInterface $input, OutputInterface $output)
    {
        if ($this->initDb($input, $output)) {
            $this->createAdminUser($input, $output);
            $version = SolidInvoiceCoreBundle::VERSION;
            $entityManager = $this->registry->getManager();

            /** @var VersionRepository $repository */
            $repository = $entityManager->getRepository(Version::class);
            $repository->updateVersion($version);
            $time = new DateTime('NOW');
            $config = ['installed' => $time->format(DateTime::ATOM)];
            $this->configWriter->dump($config);
        }
    }

    /**
     * @throws Exception|MigrationException
     */
    private function initDb(InputInterface $input, OutputInterface $output): bool
    {
        $this->createDb($input, $output);
        $callback = function ($message) use ($output): void {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln($message);
            }
        };
        $output->writeln('<info>Running database migrations</info>');
        $this->migration->migrate($callback);

        return true;
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    private function createDb(InputInterface $input, OutputInterface $output): bool
    {
        $dbName = $input->getOption('database-name');
        $params = ['driver' => $input->getOption('database-driver'), 'host' => $input->getOption('database-host'), 'port' => $input->getOption('database-port'), 'user' => $input->getOption('database-user'), 'password' => $input->getOption('database-password'), 'charset' => 'UTF8', 'driverOptions' => []];
        $tmpConnection = DriverManager::getConnection($params);

        try {
            $tmpConnection->getSchemaManager()->createDatabase($dbName);
            $output->writeln(sprintf('<info>Created database %s</info>', $dbName));
        } catch (Exception $e) {
            if (false !== strpos($e->getMessage(), 'database exists')) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    $output->writeln(sprintf('<info>Database %s already exists</info>', $dbName));
                }
            } else {
                throw $e;
            }
        }
        $params['dbname'] = $dbName;
        // Set the current connection to the new DB name
        $connection = $this->registry->getConnection();
        if ($connection->isConnected()) {
            $connection->close();
        }
        $connection->__construct($params, $connection->getDriver(), $connection->getConfiguration(), $connection->getEventManager());
        $connection->connect();

        return true;
    }

    private function createAdminUser(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Creating Admin User</info>');
        /** @var UserRepository $userRepository */

        $userRepository = $this->registry->getRepository(User::class);
        $username = $input->getOption('admin-username');
        if (null !== $userRepository->findOneBy(['username' => $username])) {
            $output->writeln(sprintf('<comment>User %s already exists, skipping creation</comment>', $username));

            return;
        }
        $user = new User();
        $user->setUsername($input->getOption('admin-username'))
            ->setEmail($input->getOption('admin-email'))
            ->setPassword($this->userPasswordEncoder->encodePassword($user, $input->getOption('admin-password')))
            ->setEnabled(true);

        $em = $this->registry->getManagerForClass(User::class);

        if (!$em instanceof ObjectManager) {
            throw new \Exception(sprintf('No object manager found for class "%s".', User::class));
        }

        $em->persist($user);
        $em->flush();
    }

    private function saveConfig(InputInterface $input)
    {
        // Don't update installed here, in case something goes wrong with the rest of the installation process
        $config = ['database_driver' => $input->getOption('database-driver'), 'database_host' => $input->getOption('database-host'), 'database_port' => $input->getOption('database-port'), 'database_name' => $input->getOption('database-name'), 'database_user' => $input->getOption('database-user'), 'database_password' => $input->getOption('database-password'), 'mailer_transport' => $input->getOption('mailer-transport'), 'mailer_host' => $input->getOption('mailer-host'), 'mailer_user' => $input->getOption('mailer-user'), 'mailer_password' => $input->getOption('mailer-password'), 'mailer_port' => $input->getOption('mailer-port'), 'mailer_encryption' => $input->getOption('mailer-encryption'), 'locale' => $input->getOption('locale'), 'currency' => $input->getOption('currency'), 'secret' => Key::createNewRandomKey()->saveToAsciiSafeString()];
        $this->configWriter->dump($config);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($this->installed) {
            throw new ApplicationInstalledException();
        }

        $currencies = array_keys(Intl::getCurrencyBundle()->getCurrencyNames());
        $locales = array_keys(Intl::getLocaleBundle()->getLocaleNames());
        $localeQuestion = new Question('<question>Please enter a locale:</question> ');
        $localeQuestion->setAutocompleterValues($locales);
        $currencyQuestion = new Question('<question>Please enter a currency:</question> ');
        $currencyQuestion->setAutocompleterValues($currencies);
        $passwordQuestion = new Question('<question>Please enter a password for the admin account:</question> ');
        $passwordQuestion->setHidden(true);
        $options = ['database-user' => new Question('<question>Please enter your database user name:</question> '), 'admin-username' => new Question('<question>Please enter a username for the admin account:</question> '), 'admin-password' => $passwordQuestion, 'admin-email' => new Question('<question>Please enter an email address for the admin account:</question> '), 'locale' => $localeQuestion, 'currency' => $currencyQuestion];
        /** @var QuestionHelper $dialog */
        $dialog = $this->getHelper('question');

        foreach ($options as $option => $question) {
            if (null === $input->getOption($option)) {
                $value = null;
                while (empty($value)) {
                    $value = $dialog->ask($input, $output, $question);
                }
                $input->setOption($option, $value);
            }
        }
    }
}
