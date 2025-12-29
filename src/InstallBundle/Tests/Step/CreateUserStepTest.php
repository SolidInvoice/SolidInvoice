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

namespace SolidInvoice\InstallBundle\Tests\Step;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\DTO\UserAccount;
use SolidInvoice\InstallBundle\Step\CreateUserStep;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * @covers \SolidInvoice\InstallBundle\Step\CreateUserStep
 */
final class CreateUserStepTest extends TestCase
{
    public function testPriority(): void
    {
        self::assertSame(5, CreateUserStep::priority());
    }

    public function testGetLabel(): void
    {
        self::assertSame('Creating admin user', CreateUserStep::getLabel());
    }

    public function testExecuteCreatesUser(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher
            ->expects(self::once())
            ->method('hash')
            ->with('test_password')
            ->willReturn('hashed_password');

        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasherFactory
            ->expects(self::once())
            ->method('getPasswordHasher')
            ->with(self::isInstanceOf(User::class))
            ->willReturn($hasher);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (User $user): bool {
                self::assertSame('test@example.com', $user->getEmail());
                self::assertSame('John', $user->getFirstName());
                self::assertSame('Doe', $user->getLastName());
                self::assertSame('hashed_password', $user->getPassword());
                self::assertTrue($user->isVerified());
                self::assertTrue($user->isEnabled());
                return true;
            }));

        $step = new CreateUserStep($userRepository, $hasherFactory);

        $installation = new Installation(
            userAccount: new UserAccount(
                locale: 'en',
                firstName: 'John',
                lastName: 'Doe',
                emailAddress: 'test@example.com',
                password: 'test_password',
            ),
        );

        $callbackMessages = [];
        $callback = static function (string $message) use (&$callbackMessages): \Generator {
            $callbackMessages[] = $message;
            yield;
        };

        $generator = $step->execute($installation, $callback);
        iterator_to_array($generator);

        self::assertContains('Admin user created', $callbackMessages);
    }

    public function testExecuteHandlesDuplicateUser(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher
            ->method('hash')
            ->willReturn('hashed_password');

        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasherFactory
            ->method('getPasswordHasher')
            ->willReturn($hasher);

        $exception = $this->createMock(UniqueConstraintViolationException::class);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->willThrowException($exception);

        $step = new CreateUserStep($userRepository, $hasherFactory);

        $installation = new Installation(
            userAccount: new UserAccount(
                locale: 'en',
                firstName: 'John',
                lastName: 'Doe',
                emailAddress: 'test@example.com',
                password: 'test_password',
            ),
        );

        $callbackMessages = [];
        $callback = static function (string $message) use (&$callbackMessages): \Generator {
            $callbackMessages[] = $message;
            yield;
        };

        $generator = $step->execute($installation, $callback);
        iterator_to_array($generator);

        self::assertContains('Admin user already exists, skipping creation', $callbackMessages);
    }

    public function testExecuteWithoutCallback(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher
            ->method('hash')
            ->willReturn('hashed_password');

        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasherFactory
            ->method('getPasswordHasher')
            ->willReturn($hasher);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('save');

        $step = new CreateUserStep($userRepository, $hasherFactory);

        $installation = new Installation(
            userAccount: new UserAccount(
                locale: 'en',
                firstName: 'John',
                lastName: 'Doe',
                emailAddress: 'test@example.com',
                password: 'test_password',
            ),
        );

        // Execute without callback - should not throw exception
        $generator = $step->execute($installation, null);
        self::assertSame([], iterator_to_array($generator));
    }
}
