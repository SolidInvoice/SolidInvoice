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

namespace SolidInvoice\CoreBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Demo\DemoMode;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoader;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use function assert;
use function iterator_to_array;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Command\DemoResetCommandTest
 */
#[AsCommand(
    name: 'solidinvoice:demo:reset',
    description: 'Reset the demo environment: drop the database, re-run migrations and reseed demo data',
)]
final class DemoResetCommand extends Command
{
    private const string LOCK_KEY = 'solidinvoice-demo-reset';

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CompanySelector $companySelector,
        private readonly DummyDataLoader $dummyDataLoader,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly Migration $migration,
        private readonly CompanyRepository $companyRepository,
        private readonly UserRepository $userRepository,
        private readonly LockFactory $lockFactory,
        private readonly DemoMode $demoMode,
    ) {
        parent::__construct();
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->demoMode->isEnabled();
    }

    protected function configure(): void
    {
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command performs a FULL reset of the demo environment:

  * Drops the entire database schema
  * Re-runs all migrations to recreate the schema
  * Creates the demo super-admin user from the <comment>SOLIDINVOICE_DEMO_USERNAME</comment>
    and <comment>SOLIDINVOICE_DEMO_PASSWORD</comment> environment variables
  * Creates a demo company and reseeds it with dummy data

This command is only registered when demo mode is enabled.

Overlapping runs are prevented with a lock, so it is safe to schedule frequently.
To keep the public demo fresh, add a cron entry (e.g. hourly) such as:

  <comment>0 * * * * php %command.full_name% -e prod -n</comment>
HELP
        );
    }

    protected function handle(): int
    {
        // Belt-and-braces guard: with Symfony's lazy console command loading,
        // a described command wrapped in LazyCommand always reports itself as
        // enabled at listing/lookup time (LazyCommand::isEnabled() defaults to
        // true and never delegates to the wrapped command), so isEnabled()
        // above only hides this command from `list`/`help` — it does NOT stop
        // it from being looked up and executed by name. This full DB wipe
        // must never run for real outside demo mode, so re-check here.
        if (! $this->demoMode->isEnabled()) {
            $this->io->error('This command can only be run when demo mode is enabled.');

            return self::FAILURE;
        }

        $lock = $this->lockFactory->createLock(self::LOCK_KEY);

        if (! $lock->acquire()) {
            $this->io->warning('Another demo reset is already running. Skipping this run.');

            return self::SUCCESS;
        }

        try {
            $username = $this->demoMode->username();
            $password = $this->demoMode->password();

            if (null === $username || null === $password) {
                $this->io->error('Demo credentials (SOLIDINVOICE_DEMO_USERNAME / SOLIDINVOICE_DEMO_PASSWORD) are not configured.');

                return self::FAILURE;
            }

            $em = $this->registry->getManager();
            assert($em instanceof EntityManagerInterface);

            $this->io->section('Dropping database schema');
            (new SchemaTool($em))->dropDatabase();

            $this->io->section('Recreating database schema');
            // migrate() executes its SQL as the generator advances; drain it fully.
            iterator_to_array($this->migration->migrate(), false);

            $this->io->section('Creating demo user');
            $user = new User();
            $user->setEmail($username)
                ->setPassword($this->userPasswordHasher->hashPassword($user, $password))
                ->setEnabled(true)
                ->setVerified(true);
            $user->setRoles(['ROLE_SUPER_ADMIN']);

            $this->io->section('Creating demo company');
            $company = new Company();
            $company->setName('Demo Company');
            $company->currency = 'USD';
            $this->companyRepository->save($company);

            $user->addCompany($company);
            $this->userRepository->save($user);

            $this->io->section('Loading demo data');
            $filters = $em->getFilters();
            $companyFilterEnabled = $filters->isEnabled('company');

            if ($companyFilterEnabled) {
                $filters->disable('company');
            }

            try {
                $this->companySelector->switchCompany($company->getId());
                $this->dummyDataLoader->load($company);
            } finally {
                $this->companySelector->reset();

                if ($companyFilterEnabled) {
                    $filters->enable('company');
                }
            }

            $this->io->success('Demo environment reset successfully.');
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }
}
