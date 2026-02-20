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

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\Command\InstallCommand;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\KernelInterface;
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
            ->with(M::on(function (User $user) use ($email, $hashedPassword): bool {
                return $user->getEmail() === $email
                    && $user->getPassword() === $hashedPassword
                    && $user->isEnabled()
                    && $user->isVerified();
            }));
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

    private function createCommand(
        ManagerRegistry $registry,
        ?UserPasswordHasherInterface $passwordHasher = null,
    ): InstallCommand {
        $vault = $this->createMock(AbstractVault::class);
        $configWriter = new ConfigWriter($vault, '/tmp/test-secrets');

        return new InstallCommand(
            $configWriter,
            $registry,
            $passwordHasher ?? M::mock(UserPasswordHasherInterface::class),
            new ServiceLocator([]),
            $this->createMock(KernelInterface::class),
            '/tmp/test',
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
