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

namespace SolidInvoice\CoreBundle\Tests\Command;

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Configuration as MigrationsConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SolidInvoice\CoreBundle\Command\DemoResetCommand;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoader;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoaderInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use function sys_get_temp_dir;
use function uniqid;

/**
 * `Migration`, `DummyDataLoader`, `CompanySelector` and `IO` are all `final`
 * classes, so per the project's Mockery erratum they cannot be mocked or
 * stubbed when the double must satisfy the constructor's type hint. Real
 * instances are constructed instead, wrapping mockable interfaces
 * (`ManagerRegistry`, `DependencyFactory` is not final and is mockable) where
 * needed. None of these real objects have their methods invoked in the
 * "lock not acquired" path under test, since the command returns before
 * reaching them.
 *
 * The happy-path test below (`testHandleRunsFullResetSequenceWhenDemoModeEnabledAndLockAcquired`)
 * does exercise `Migration::migrate()` and the command's own
 * `(new SchemaTool($em))->dropDatabase()` call for real: those two operations
 * are so deeply intertwined with Doctrine ORM/DBAL/Migrations internals
 * (schema introspection, connection platforms, metadata storage) that
 * mocking them at the unit level would either be impossible (several of the
 * classes involved, e.g. `Doctrine\Migrations\Metadata\MigrationPlanList`
 * and `Doctrine\Migrations\Version\Version`, are `final`) or so deep that the
 * mocks would just re-implement Doctrine's internals and verify nothing
 * real. Instead, a real `Doctrine\ORM\EntityManager` backed by an in-memory
 * SQLite connection with zero mapped entities is used for the
 * command's/`Migration`'s/`CompanySelector`'s shared `ManagerRegistry`
 * (mirroring production, where all three receive the same `doctrine`
 * service): this makes `dropDatabase()`, `migrate()`'s schema diffing, and
 * the `company` Doctrine filter enable/disable run their real code paths
 * against a trivial, empty database, while every business-logic collaborator
 * (`CompanyRepository`, `UserRepository`, `UserPasswordHasherInterface`, the
 * `DummyDataLoaderInterface` loader) is a Mockery mock with real
 * expectations on the final user/company state.
 *
 * Known, accepted limitation: because there are zero mapped entities, the
 * ORM metadata passed to `Migration::migrate()` is always empty, so
 * `SchemaTool::getUpdateSchemaSql()` always returns `[]` and `migrate()`'s
 * actual SQL-execution branch (`if ($updateSchemaSql !== [])`) is never
 * exercised here -- only the "already up to date, generator drains cleanly"
 * branch is. Proving the execution branch would require a real mapped
 * entity (so the diff produces actual DDL), which is a materially bigger
 * setup than this unit-level test warrants; that branch is left to be
 * covered at the integration/functional level instead.
 */

final class DemoResetCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testIsEnabledReflectsDemoMode(): void
    {
        $demoModeResolver = new ModeResolver('demo', 'demo@example.com', 'demo-password');
        $command = $this->createCommand(modeResolver: $demoModeResolver);

        self::assertTrue($command->isEnabled());

        $selfHostedModeResolver = new ModeResolver();
        $command = $this->createCommand(modeResolver: $selfHostedModeResolver);

        self::assertFalse($command->isEnabled());
    }

    public function testHandleFailsWhenDemoModeDisabled(): void
    {
        // Symfony's lazy console command loading (LazyCommand::isEnabled() defaults to
        // true and never delegates to the wrapped command) means isEnabled() only hides
        // this command from `list`/`help`; it does not stop the command from being
        // looked up and executed by name. handle() must independently refuse to run a
        // full DB wipe when demo mode is off, so this is asserted directly here.
        $modeResolver = new ModeResolver();

        $lockFactory = M::mock(LockFactory::class);
        $lockFactory->shouldNotReceive('createLock');

        $output = new BufferedOutput();
        $io = new IO(new ArrayInput([]), $output);

        $command = $this->createCommand(lockFactory: $lockFactory, modeResolver: $modeResolver);
        $command->setIo($io);

        self::assertSame(Command::FAILURE, $this->invokeHandle($command));
        self::assertStringContainsString('This command can only be run when demo mode is enabled', $output->fetch());
    }

    public function testHandleSkipsWhenLockNotAcquired(): void
    {
        // LockFactory::createLock() is declared to return SharedLockInterface (not the
        // narrower LockInterface), so the mock must satisfy that return type.
        $lock = M::mock(SharedLockInterface::class);
        $lock->shouldReceive('acquire')->once()->andReturnFalse();
        $lock->shouldNotReceive('release');

        $lockFactory = M::mock(LockFactory::class);
        $lockFactory->shouldReceive('createLock')->once()->andReturn($lock);

        // Migration is final; DependencyFactory is not, so it's mockable.
        // migrate() is never called on this real instance in this path.
        $migration = new Migration(M::mock(DependencyFactory::class), M::mock(ManagerRegistry::class));

        // DummyDataLoader is final; load() is never called in this path.
        $dummyDataLoader = new DummyDataLoader([]);

        $output = new BufferedOutput();
        $io = new IO(new ArrayInput([]), $output);

        $command = $this->createCommand(
            lockFactory: $lockFactory,
            migration: $migration,
            dummyDataLoader: $dummyDataLoader,
        );
        $command->setIo($io);

        self::assertSame(Command::SUCCESS, $this->invokeHandle($command));
        self::assertStringContainsString('Another demo reset is already running', $output->fetch());
    }

    public function testHandleRunsFullResetSequenceWhenDemoModeEnabledAndLockAcquired(): void
    {
        $filesystem = new Filesystem();
        $emptyEntitiesDir = sys_get_temp_dir() . '/solidinvoice-demo-reset-test-' . uniqid();
        $filesystem->mkdir($emptyEntitiesDir);

        try {
            // A real EntityManager backed by an in-memory SQLite connection with no
            // mapped entities. See the class docblock for why this is used instead of
            // mocking SchemaTool/Migration's internals. It is shared by the command,
            // Migration and CompanySelector below, exactly as the `doctrine` service
            // is shared by all three in production.
            $ormConfig = ORMSetup::createAttributeMetadataConfiguration(paths: [$emptyEntitiesDir], isDevMode: true);
            $ormConfig->addFilter('company', CompanyFilter::class);
            $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $ormConfig);
            $em = new EntityManager($connection, $ormConfig);

            // Seed a real table so dropDatabase() has something to actually drop, making
            // that call a falsifiable assertion instead of the permanent no-op it is with
            // zero mapped entities (dropDatabase() always introspects an empty schema and
            // produces no SQL unless there is at least one real table present).
            //
            // The table is deliberately named after the migrations metadata storage table
            // (see below) rather than something arbitrary like "probe". With zero mapped
            // entities, `Migration::migrate()`'s own schema diffing (see the big comment in
            // Migration.php) treats *any* unmapped table as an orphan and emits a DROP for
            // it too -- so an arbitrarily-named probe table gets removed by migrate() even
            // if dropDatabase() is skipped entirely, making the mutation undetectable (this
            // was verified by hand: commenting out dropDatabase() in the command still left
            // the test green). migrate() explicitly excludes the migrations table name from
            // its own diffing (to avoid dropping its own bookkeeping table), so a table with
            // that exact name is the one candidate that only dropDatabase() -- never
            // migrate() -- will remove, making its disappearance proof that dropDatabase()
            // specifically ran.
            $migrationsTableName = (new TableMetadataStorageConfiguration())->getTableName();
            $connection->executeStatement("CREATE TABLE {$migrationsTableName} (id INTEGER)");
            self::assertContains($migrationsTableName, $connection->createSchemaManager()->listTableNames());

            $registry = M::mock(ManagerRegistry::class);
            $registry->shouldReceive('getManager')->andReturn($em);

            $migrationsConfiguration = new MigrationsConfiguration();
            $migrationsConfiguration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());

            // Migration is final; DependencyFactory is not, so it's mockable. Its
            // internals are stubbed to produce an empty, already-up-to-date plan so
            // that migrate()'s generator drains without needing real migration files,
            // while the schema-diffing it performs runs for real against $em above.
            $metadataStorage = M::mock(MetadataStorage::class);
            $metadataStorage->shouldReceive('ensureInitialized')->once();

            $planCalculator = M::mock(MigrationPlanCalculator::class);
            $planCalculator->shouldReceive('getPlanUntilVersion')->once()->andReturn(new MigrationPlanList([], 'up'));

            $aliasResolver = M::mock(AliasResolver::class);
            $aliasResolver->shouldReceive('resolveVersionAlias')->once()->with('latest')->andReturn(new Version('1'));

            $dependencyFactory = M::mock(DependencyFactory::class);
            $dependencyFactory->shouldReceive('getMetadataStorage')->once()->andReturn($metadataStorage);
            $dependencyFactory->shouldReceive('getMigrationPlanCalculator')->once()->andReturn($planCalculator);
            $dependencyFactory->shouldReceive('getVersionAliasResolver')->once()->andReturn($aliasResolver);
            $dependencyFactory->shouldReceive('getConfiguration')->once()->andReturn($migrationsConfiguration);

            $migration = new Migration($dependencyFactory, $registry);

            // CompanySelector is final; ManagerRegistry is mockable, but here the real
            // shared $em is used instead (see class docblock) so switchCompany()/reset()
            // exercise the real 'company' Doctrine filter enable/disable.
            $companySelector = new CompanySelector($registry);

            $dummyDataLoaderInner = M::mock(DummyDataLoaderInterface::class);
            $dummyDataLoaderInner->shouldReceive('load')->once()->with(M::on(
                static fn (Company $company): bool => 'Demo Company' === $company->getName() && 'USD' === $company->currency
            ));
            $dummyDataLoader = new DummyDataLoader([$dummyDataLoaderInner]);

            $userPasswordHasher = M::mock(UserPasswordHasherInterface::class);
            $userPasswordHasher->shouldReceive('hashPassword')
                ->once()
                ->with(M::type(User::class), 'demo-password')
                ->andReturn('hashed-demo-password');

            $companyRepository = M::mock(CompanyRepository::class);
            $companyRepository->shouldReceive('save')
                ->once()
                ->ordered()
                ->with(M::on(
                    static fn (Company $company): bool => 'Demo Company' === $company->getName() && 'USD' === $company->currency
                ));

            $userRepository = M::mock(UserRepository::class);
            $userRepository->shouldReceive('save')
                ->once()
                ->ordered()
                ->with(M::on(
                    static fn (User $user): bool => 'demo@example.com' === $user->getEmail()
                        && 'hashed-demo-password' === $user->getPassword()
                        && true === $user->isEnabled()
                        && true === $user->isVerified()
                        && in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)
                        && 1 === $user->getCompanies()->count()
                        && $user->getCompanies()->exists(
                            static fn (int $key, Company $company): bool => 'Demo Company' === $company->getName() && 'USD' === $company->currency
                        )
                ));

            $lock = M::mock(SharedLockInterface::class);
            $lock->shouldReceive('acquire')->once()->andReturnTrue();
            $lock->shouldReceive('release')->once();

            $lockFactory = M::mock(LockFactory::class);
            $lockFactory->shouldReceive('createLock')->once()->andReturn($lock);

            $modeResolver = new ModeResolver('demo', 'demo@example.com', 'demo-password');

            $output = new BufferedOutput();
            $io = new IO(new ArrayInput([]), $output);

            $command = $this->createCommand(
                registry: $registry,
                companySelector: $companySelector,
                dummyDataLoader: $dummyDataLoader,
                userPasswordHasher: $userPasswordHasher,
                migration: $migration,
                companyRepository: $companyRepository,
                userRepository: $userRepository,
                lockFactory: $lockFactory,
                modeResolver: $modeResolver,
            );
            $command->setIo($io);

            self::assertSame(Command::SUCCESS, $this->invokeHandle($command));
            self::assertStringContainsString('Demo environment reset successfully', $output->fetch());

            // Falsifiable assertion that dropDatabase() actually ran: the seeded table
            // above must be gone. migrate() deliberately never touches a table with this
            // name (see the comment where it's created above), so this can only have
            // been removed by the command's own dropDatabase() call.
            self::assertNotContains($migrationsTableName, $connection->createSchemaManager()->listTableNames());
        } finally {
            $filesystem->remove($emptyEntitiesDir);
        }
    }

    private function createCommand(
        ?ManagerRegistry $registry = null,
        ?CompanySelector $companySelector = null,
        ?DummyDataLoader $dummyDataLoader = null,
        ?UserPasswordHasherInterface $userPasswordHasher = null,
        ?Migration $migration = null,
        ?CompanyRepository $companyRepository = null,
        ?UserRepository $userRepository = null,
        ?LockFactory $lockFactory = null,
        ?ModeResolver $modeResolver = null,
    ): DemoResetCommand {
        return new DemoResetCommand(
            $registry ?? M::mock(ManagerRegistry::class),
            // CompanySelector is final; ManagerRegistry (interface) is mockable.
            $companySelector ?? new CompanySelector(M::mock(ManagerRegistry::class)),
            $dummyDataLoader ?? new DummyDataLoader([]),
            $userPasswordHasher ?? M::mock(UserPasswordHasherInterface::class),
            $migration ?? new Migration(M::mock(DependencyFactory::class), M::mock(ManagerRegistry::class)),
            $companyRepository ?? M::mock(CompanyRepository::class),
            $userRepository ?? M::mock(UserRepository::class),
            $lockFactory ?? M::mock(LockFactory::class),
            $modeResolver ?? new ModeResolver('demo', 'demo@example.com', 'demo-password'),
        );
    }

    private function invokeHandle(DemoResetCommand $command): int
    {
        return (new ReflectionMethod(DemoResetCommand::class, 'handle'))->invoke($command);
    }
}
