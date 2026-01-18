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
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class InstallCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateAdminUserSkipsWhenUserExists(): void
    {
        $email = 'existing@example.com';

        $userRepository = M::mock(UserRepository::class);
        $userRepository->shouldReceive('loadUserByIdentifier')
            ->with($email)
            ->once()
            ->andReturn(new User());

        $registry = M::mock(ManagerRegistry::class);
        $registry->shouldReceive('getRepository')
            ->with(User::class)
            ->andReturn($userRepository);

        // EntityManager should NOT be called since user exists
        $registry->shouldNotReceive('getManagerForClass');

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
        $userRepository->shouldReceive('loadUserByIdentifier')
            ->with($email)
            ->once()
            ->andThrow(new UserNotFoundException());

        $entityManager = M::mock(ObjectManager::class);
        $entityManager->shouldReceive('persist')
            ->once()
            ->with(M::on(function (User $user) use ($email, $hashedPassword): bool {
                return $user->getEmail() === $email
                    && $user->getPassword() === $hashedPassword
                    && $user->isEnabled();
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
        // Should NOT receive "already exists" message
        $output->shouldNotReceive('writeln')
            ->with(M::pattern('/already exists/'));

        $command = $this->createCommand($registry, $passwordHasher);

        $this->invokeCreateAdminUser($command, $input, $output);
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
