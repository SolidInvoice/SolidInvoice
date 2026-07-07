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

namespace SolidInvoice\InstallBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\InstallBundle\Command\InstallCommand;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class InstallCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateAdminUserSkipsWhenEnabledUserExists(): void
    {
        $email = 'existing@example.com';

        $existingUser = new User();
        $existingUser->setEmail($email)->setEnabled(true);

        $userRepository = M::mock(UserRepository::class);
        $userRepository->shouldReceive('findOneBy')
            ->with(['email' => $email])
            ->once()
            ->andReturn($existingUser);

        $entityManager = M::mock(ObjectManager::class);
        // Should NOT persist since user already exists and is enabled
        $entityManager->shouldNotReceive('persist');
        $entityManager->shouldNotReceive('flush');

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getRepository')
            ->with(User::class)
            ->andReturn($userRepository);
        $registry->shouldReceive('getManagerForClass')
            ->with(User::class)
            ->andReturn($entityManager);

        $input = M::mock(InputInterface::class);
        $input->shouldReceive('getOption')
            ->with('admin-email')
            ->andReturn($email);

        $output = M::mock(OutputInterface::class);
        $output->shouldReceive('writeln')
            ->with('<info>Creating Admin User</info>')
            ->once();
        $output->shouldReceive('writeln')
            ->with(sprintf('<comment>User %s already exists, skipping creation</comment>', $email))
            ->once();

        $command = $this->createCommand($registry);

        $this->invokeCreateAdminUser($command, $input, $output);
    }

    public function testCreateAdminUserCreatesUserWhenNotExists(): void
    {
        $email = 'new@example.com';
        $password = 'secret123';
        $hashedPassword = 'hashed_secret123';

        $userRepository = M::mock(UserRepository::class);
        $userRepository->shouldReceive('findOneBy')
            ->with(['email' => $email])
            ->once()
            ->andReturn(null);

        $entityManager = M::mock(ObjectManager::class);
        $entityManager->shouldReceive('persist')
            ->once()
            ->with(M::on(fn (User $user): bool => $user->getEmail() === $email
                && $user->getPassword() === $hashedPassword
                && $user->isEnabled()
                && $user->isVerified()));
        $entityManager->shouldReceive('flush')
            ->once();

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getRepository')
            ->with(User::class)
            ->andReturn($userRepository);
        $registry->shouldReceive('getManagerForClass')
            ->with(User::class)
            ->andReturn($entityManager);

        $passwordHasher = M::mock(UserPasswordHasherInterface::class);
        $passwordHasher->shouldReceive('hashPassword')
            ->once()
            ->with(M::type(User::class), $password)
            ->andReturn($hashedPassword);

        $input = M::mock(InputInterface::class);
        $input->shouldReceive('getOption')
            ->with('admin-email')
            ->andReturn($email);
        $input->shouldReceive('getOption')
            ->with('admin-password')
            ->andReturn($password);

        $output = M::mock(OutputInterface::class);
        $output->shouldReceive('writeln')
            ->with('<info>Creating Admin User</info>')
            ->once();

        $command = $this->createCommand($registry, $passwordHasher);

        $this->invokeCreateAdminUser($command, $input, $output);
    }

    public function testCreateAdminUserReEnablesDisabledUser(): void
    {
        $email = 'disabled@example.com';
        $password = 'newpassword123';
        $hashedPassword = 'hashed_newpassword123';

        $disabledUser = new User();
        $disabledUser->setEmail($email)->setEnabled(false)->setPassword('old_password');

        $userRepository = M::mock(UserRepository::class);
        $userRepository->shouldReceive('findOneBy')
            ->with(['email' => $email])
            ->once()
            ->andReturn($disabledUser);

        $entityManager = M::mock(ObjectManager::class);
        // Should NOT persist (user already exists), just flush
        $entityManager->shouldNotReceive('persist');
        $entityManager->shouldReceive('flush')
            ->once();

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getRepository')
            ->with(User::class)
            ->andReturn($userRepository);
        $registry->shouldReceive('getManagerForClass')
            ->with(User::class)
            ->andReturn($entityManager);

        $passwordHasher = M::mock(UserPasswordHasherInterface::class);
        $passwordHasher->shouldReceive('hashPassword')
            ->once()
            ->with($disabledUser, $password)
            ->andReturn($hashedPassword);

        $input = M::mock(InputInterface::class);
        $input->shouldReceive('getOption')
            ->with('admin-email')
            ->andReturn($email);
        $input->shouldReceive('getOption')
            ->with('admin-password')
            ->andReturn($password);

        $output = M::mock(OutputInterface::class);
        $output->shouldReceive('writeln')
            ->with('<info>Creating Admin User</info>')
            ->once();
        $output->shouldReceive('writeln')
            ->with(sprintf('<comment>Re-enabling disabled user (%s), and resetting password</comment>', $email))
            ->once();

        $command = $this->createCommand($registry, $passwordHasher);

        $this->invokeCreateAdminUser($command, $input, $output);

        // Verify user was re-enabled, verified, and password updated
        self::assertTrue($disabledUser->isEnabled());
        self::assertTrue($disabledUser->isVerified());
        self::assertSame($hashedPassword, $disabledUser->getPassword());
    }

    public function testDisableTelemetryOptionIsRegistered(): void
    {
        $registry = M::mock(ManagerRegistry::class);
        $command = $this->createCommand($registry);

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasOption('disable-telemetry'));

        $option = $definition->getOption('disable-telemetry');
        self::assertFalse($option->acceptValue());
        self::assertSame('Disable sending anonymous usage statistics', $option->getDescription());
    }

    public function testSaveConfigWritesDatabaseUrlForSqlite(): void
    {
        $configDir = sys_get_temp_dir() . '/solidinvoice-install-test';
        $expectedDsn = sprintf('sqlite:///%s/db/solidinvoice.db', $configDir);

        $sealed = [];
        $vault = M::mock(AbstractVault::class);
        $vault->shouldReceive('generateKeys')->andReturnTrue();
        $vault->shouldReceive('seal')->andReturnUsing(
            static function (string $name, string $value) use (&$sealed): void {
                $sealed[$name] = $value;
            }
        );

        $configWriter = new ConfigWriter($vault, $configDir);

        $input = M::mock(InputInterface::class);
        $input->shouldReceive('getOption')->with('database-driver')->andReturn('pdo_sqlite');
        // No path provided, so the default location is used.
        $input->shouldReceive('getOption')->with('database-name')->andReturnNull();
        $input->shouldReceive('getOption')->with('database-host')->andReturnNull();
        $input->shouldReceive('getOption')->with('database-port')->andReturnNull();
        $input->shouldReceive('getOption')->with('database-user')->andReturnNull();
        $input->shouldReceive('getOption')->with('database-password')->andReturnNull();
        $input->shouldReceive('getOption')->with('locale')->andReturn('en');
        $input->shouldReceive('getOption')->with('application-url')->andReturn('https://example.com');
        $input->shouldReceive('getOption')->with('disable-telemetry')->andReturnTrue();

        $command = $this->createCommand(M::mock(ManagerRegistry::class), null, $configWriter, $configDir);

        $method = new ReflectionMethod(InstallCommand::class, 'saveConfig');
        $method->invoke($command, $input);

        self::assertArrayHasKey('SOLIDINVOICE_DATABASE_URL', $sealed);
        self::assertSame($expectedDsn, $sealed['SOLIDINVOICE_DATABASE_URL']);
        self::assertDirectoryExists($configDir . '/db');
    }

    public function testValidateDoesNotRequireHostUserOrApplicationUrlForSqlite(): void
    {
        $input = M::mock(InputInterface::class);
        $input->shouldReceive('getOption')->with('database-driver')->andReturn('pdo_sqlite');
        $input->shouldReceive('getOption')->with('skip-user')->andReturnTrue();
        $input->shouldReceive('getOption')->with('locale')->andReturn('en');
        // database-host / database-user / application-url are intentionally never
        // provided: host/user do not apply to SQLite and the application URL is optional.
        $input->shouldReceive('getOption')->with('application-url')->andReturnNull();

        $command = $this->createCommand(M::mock(ManagerRegistry::class));

        $method = new ReflectionMethod(InstallCommand::class, 'validate');

        // Should not throw for missing host/user/application-url when using SQLite.
        self::assertSame($command, $method->invoke($command, $input));
    }

    public function testInstallIteratesStepGenerators(): void
    {
        // Regression: installation steps implement execute() as generators. If the
        // returned generator is not iterated, the step body never runs — which meant
        // the database schema was never created and installs failed with
        // "no such table: users" while creating the admin user.
        $ran = (object) ['value' => false];

        $step = new readonly class($ran) implements InstallationStepInterface {
            public function __construct(
                private object $ran
            ) {
            }

            public static function priority(): int
            {
                return 100;
            }

            public function execute(Installation $installationData, ?callable $callback = null): Generator
            {
                $this->ran->value = true;

                yield;
            }

            public static function getLabel(): string
            {
                return 'Spy step';
            }
        };

        $versionRepository = M::mock(VersionRepository::class);
        $versionRepository->shouldReceive('updateVersion')->once();

        $entityManager = M::mock(ObjectManager::class);
        $entityManager->shouldReceive('getRepository')->with(Version::class)->andReturn($versionRepository);

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->andReturn($entityManager);

        $input = M::mock(InputInterface::class);
        $input->shouldReceive('getOption')->with('skip-user')->andReturnTrue();

        $output = M::mock(OutputInterface::class);
        $output->shouldReceive('writeln');

        $steps = new ServiceLocator(['Spy step' => static fn (): InstallationStepInterface => $step]);

        $command = $this->createCommand($registry, null, null, '/tmp/test-config', $steps);

        new ReflectionMethod(InstallCommand::class, 'install')->invoke($command, $input, $output);

        self::assertTrue($ran->value, 'The installation step generator should have been executed.');
    }

    /**
     * @param ServiceLocator<InstallationStepInterface>|null $steps
     */
    private function createCommand(
        ManagerRegistry $registry,
        ?UserPasswordHasherInterface $passwordHasher = null,
        ?ConfigWriter $configWriter = null,
        string $configDir = '/tmp/test-config',
        ?ServiceLocator $steps = null,
    ): InstallCommand {
        $configWriter ??= new ConfigWriter($this->createStub(AbstractVault::class), '/tmp/test-secrets');

        // Telemetry is disabled here (null build ID), so it no-ops and never
        // touches the message bus or connection during the command tests.
        $telemetry = new Telemetry(
            $this->createStub(MessageBusInterface::class),
            $configWriter,
            $this->createStub(Connection::class),
            null,
            false,
            '',
            false,
            'en',
            null,
        );

        return new InstallCommand(
            $configWriter,
            $registry,
            $passwordHasher ?? M::mock(UserPasswordHasherInterface::class),
            $steps ?? new ServiceLocator([]),
            $this->createStub(KernelInterface::class),
            $telemetry,
            $configDir,
            null
        );
    }

    private function invokeCreateAdminUser(
        InstallCommand $command,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $method = new ReflectionMethod(InstallCommand::class, 'createAdminUser');
        $method->invoke($command, $input, $output);
    }
}
