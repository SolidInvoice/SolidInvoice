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

namespace SolidInvoice\UserBundle\Tests\OAuth;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\OAuth\GoogleIdentity;
use SolidInvoice\UserBundle\OAuth\GoogleUserProvisioner;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\Toggler\ToggleInterface;

#[CoversClass(GoogleUserProvisioner::class)]
final class GoogleUserProvisionerTest extends TestCase
{
    private EntityManagerInterface & MockObject $entityManager;

    private UserRepository & MockObject $repository;

    private ToggleInterface & MockObject $toggle;

    private GoogleUserProvisioner $provisioner;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(UserRepository::class);
        $this->toggle = $this->createMock(ToggleInterface::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->provisioner = new GoogleUserProvisioner($this->entityManager, $this->toggle);
    }

    public function testReturnsExistingUserMatchedByGoogleId(): void
    {
        $existing = new User();
        $this->repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?User => isset($criteria['googleId']) ? $existing : null,
        );

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->provisioner->findOrCreate($this->identity());

        self::assertNotNull($result);
        self::assertSame($existing, $result->user);
        self::assertFalse($result->isNew);
    }

    public function testLinksGoogleIdToExistingAccountWithVerifiedEmail(): void
    {
        $byEmail = new User();
        $byEmail->setEmail('user@example.com');

        $this->repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?User => isset($criteria['email']) ? $byEmail : null,
        );

        $this->entityManager->expects($this->once())->method('persist')->with($byEmail);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->provisioner->findOrCreate($this->identity(emailVerified: true));

        self::assertNotNull($result);
        self::assertSame($byEmail, $result->user);
        self::assertFalse($result->isNew);
        self::assertSame('google-id-123', $byEmail->getGoogleId());
    }

    public function testRejectsLinkingToExistingAccountWhenEmailNotVerified(): void
    {
        $byEmail = new User();

        $this->repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?User => isset($criteria['email']) ? $byEmail : null,
        );

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->provisioner->findOrCreate($this->identity(emailVerified: false));

        self::assertNull($result);
        self::assertNull($byEmail->getGoogleId());
    }

    public function testLinksGoogleIdToTheCurrentlyLoggedInUser(): void
    {
        $current = new User();
        $current->setEmail('current@example.com');

        $this->repository->method('findOneBy')->willReturn(null);

        $this->entityManager->expects($this->once())->method('persist')->with($current);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->provisioner->findOrCreate($this->identity(), $current);

        self::assertNotNull($result);
        self::assertSame($current, $result->user);
        self::assertFalse($result->isNew);
        self::assertSame('google-id-123', $current->getGoogleId());
    }

    public function testCreatesNewUserWhenRegistrationIsAllowed(): void
    {
        $this->repository->method('findOneBy')->willReturn(null);
        $this->toggle->method('isActive')->willReturn(true);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->provisioner->findOrCreate($this->identity(emailVerified: true));

        self::assertNotNull($result);
        self::assertTrue($result->isNew);
        self::assertSame('user@example.com', $result->user->getEmail());
        self::assertSame('Ada', $result->user->getFirstName());
        self::assertSame('Lovelace', $result->user->getLastName());
        self::assertSame('google-id-123', $result->user->getGoogleId());
        self::assertTrue($result->user->isEnabled());
        self::assertTrue($result->user->isVerified());
        self::assertNotEmpty($result->user->getPassword());
    }

    public function testReturnsNullWhenRegistrationIsDisabled(): void
    {
        $this->repository->method('findOneBy')->willReturn(null);
        $this->toggle->method('isActive')->willReturn(false);

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->provisioner->findOrCreate($this->identity());

        self::assertNull($result);
    }

    private function identity(bool $emailVerified = true): GoogleIdentity
    {
        return new GoogleIdentity(
            googleId: 'google-id-123',
            email: 'user@example.com',
            emailVerified: $emailVerified,
            firstName: 'Ada',
            lastName: 'Lovelace',
        );
    }
}
