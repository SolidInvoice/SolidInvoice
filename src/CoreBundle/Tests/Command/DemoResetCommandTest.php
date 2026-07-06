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

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SolidInvoice\CoreBundle\Command\DemoResetCommand;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Demo\DemoMode;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoader;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * `Migration`, `DummyDataLoader`, `CompanySelector` and `IO` are all `final`
 * classes, so per the project's Mockery erratum they cannot be mocked or
 * stubbed when the double must satisfy the constructor's type hint. Real
 * instances are constructed instead, wrapping mockable interfaces
 * (`ManagerRegistry`, `DependencyFactory` is not final and is mockable) where
 * needed. None of these real objects have their methods invoked in the
 * "lock not acquired" path under test, since the command returns before
 * reaching them.
 */

final class DemoResetCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testIsEnabledReflectsDemoMode(): void
    {
        $toggle = M::mock(ToggleInterface::class);
        $toggle->shouldReceive('isActive')->with('demo_enabled')->andReturn(true, false);

        $demoMode = new DemoMode($toggle, 'demo@example.com', 'demo-password', 'https://signup.example.com');

        $command = $this->createCommand(demoMode: $demoMode);

        self::assertTrue($command->isEnabled());
        self::assertFalse($command->isEnabled());
    }

    public function testHandleFailsWhenDemoModeDisabled(): void
    {
        // Symfony's lazy console command loading (LazyCommand::isEnabled() defaults to
        // true and never delegates to the wrapped command) means isEnabled() only hides
        // this command from `list`/`help`; it does not stop the command from being
        // looked up and executed by name. handle() must independently refuse to run a
        // full DB wipe when demo mode is off, so this is asserted directly here.
        $toggle = M::mock(ToggleInterface::class);
        $toggle->shouldReceive('isActive')->with('demo_enabled')->andReturn(false);
        $demoMode = new DemoMode($toggle, 'demo@example.com', 'demo-password', 'https://signup.example.com');

        $lockFactory = M::mock(LockFactory::class);
        $lockFactory->shouldNotReceive('createLock');

        $output = new BufferedOutput();
        $io = new IO(new ArrayInput([]), $output);

        $command = $this->createCommand(lockFactory: $lockFactory, demoMode: $demoMode);
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

    private function createCommand(
        ?ManagerRegistry $registry = null,
        ?CompanySelector $companySelector = null,
        ?DummyDataLoader $dummyDataLoader = null,
        ?UserPasswordHasherInterface $userPasswordHasher = null,
        ?Migration $migration = null,
        ?CompanyRepository $companyRepository = null,
        ?UserRepository $userRepository = null,
        ?LockFactory $lockFactory = null,
        ?DemoMode $demoMode = null,
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
            $demoMode ?? $this->defaultDemoMode(),
        );
    }

    private function defaultDemoMode(): DemoMode
    {
        $toggle = M::mock(ToggleInterface::class);
        $toggle->shouldReceive('isActive')->with('demo_enabled')->andReturn(true)->byDefault();

        return new DemoMode($toggle, 'demo@example.com', 'demo-password', 'https://signup.example.com');
    }

    private function invokeHandle(DemoResetCommand $command): int
    {
        return (new ReflectionMethod(DemoResetCommand::class, 'handle'))->invoke($command);
    }
}
