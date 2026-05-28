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

namespace SolidInvoice\PaymentBundle\Tests\Listener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Payum\Core\Model\Identity;
use Payum\Core\Storage\IdentityInterface;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use SolidInvoice\PaymentBundle\Listener\Doctrine\SecurityTokenPaymentLinker;
use Symfony\Component\Uid\Ulid;

/** @covers \SolidInvoice\PaymentBundle\Listener\Doctrine\SecurityTokenPaymentLinker */
final class SecurityTokenPaymentLinkerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPrePersistSetsPaymentWhenIdentityPointsToPayment(): void
    {
        $payment = new Payment();
        $token = new SecurityToken();
        $ulid = new Ulid();
        $token->setDetails(new Identity((string) $ulid, Payment::class));

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock();
        $em->shouldReceive('getRepository')->with(Payment::class)->andReturn($repo);
        $repo->shouldReceive('find')
            ->once()
            ->with(Mockery::on(static fn (mixed $id): bool => $id instanceof Ulid && (string) $id === (string) $ulid))
            ->andReturn($payment);

        $args = new PrePersistEventArgs($token, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->prePersist($args);

        self::assertSame($payment, $token->getPayment());
    }

    public function testPrePersistIgnoresNonSecurityToken(): void
    {
        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');

        $args = new PrePersistEventArgs(new Payment(), $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->prePersist($args);
    }

    public function testPrePersistIgnoresIdentityForNonPaymentClass(): void
    {
        $token = new SecurityToken();
        $token->setDetails(new Identity((string) new Ulid(), Client::class));

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');

        $args = new PrePersistEventArgs($token, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->prePersist($args);

        self::assertNull($token->getPayment());
    }

    public function testPrePersistIgnoresNonConcreteIdentityDetails(): void
    {
        $token = new SecurityToken();
        $token->setDetails(Mockery::mock(IdentityInterface::class));

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');

        $args = new PrePersistEventArgs($token, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->prePersist($args);

        self::assertNull($token->getPayment());
    }

    public function testPrePersistSkipsInvalidUlidString(): void
    {
        $token = new SecurityToken();
        // 'invalid' is 7 chars - too short for ULID, causes Ulid::fromString to throw
        $token->setDetails(new Identity('invalid', Payment::class));

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');

        $args = new PrePersistEventArgs($token, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->prePersist($args);

        self::assertNull($token->getPayment());
    }

    public function testPreUpdateSetsPaymentAndRecomputesChangeSet(): void
    {
        $payment = new Payment();
        $token = new SecurityToken();
        $ulid = new Ulid();
        $token->setDetails(new Identity((string) $ulid, Payment::class));

        $uow = Mockery::mock(UnitOfWork::class);
        $metadata = Mockery::mock(ClassMetadata::class);

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock();
        $em->shouldReceive('getRepository')->with(Payment::class)->andReturn($repo);
        $repo->shouldReceive('find')
            ->once()
            ->with(Mockery::on(static fn (mixed $id): bool => $id instanceof Ulid && (string) $id === (string) $ulid))
            ->andReturn($payment);
        $em->shouldReceive('getUnitOfWork')->andReturn($uow);
        $em->shouldReceive('getClassMetadata')->with(SecurityToken::class)->andReturn($metadata);
        $uow->shouldReceive('recomputeSingleEntityChangeSet')->once()->with($metadata, $token);

        $changeSet = [];
        $args = new PreUpdateEventArgs($token, $em, $changeSet);

        $listener = new SecurityTokenPaymentLinker();
        $listener->preUpdate($args);

        self::assertSame($payment, $token->getPayment());
    }

    public function testPreUpdateIgnoresNonSecurityToken(): void
    {
        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');
        $em->shouldNotReceive('getUnitOfWork');

        $changeSet = [];
        $args = new PreUpdateEventArgs(new Payment(), $em, $changeSet);

        $listener = new SecurityTokenPaymentLinker();
        $listener->preUpdate($args);
    }

    public function testPreRemoveDeletesAssociatedTokensForPayment(): void
    {
        $payment = new Payment();
        $token1 = new SecurityToken();
        $token2 = new SecurityToken();

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock();
        $em->shouldReceive('getRepository')->with(SecurityToken::class)->andReturn($repo);
        $repo->shouldReceive('findBy')->once()->with(['payment' => $payment])->andReturn([$token1, $token2]);
        $em->shouldReceive('remove')->once()->with($token1);
        $em->shouldReceive('remove')->once()->with($token2);

        $args = new PreRemoveEventArgs($payment, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->preRemove($args);
    }

    public function testPreRemoveIgnoresNonPaymentEntity(): void
    {
        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');

        $args = new PreRemoveEventArgs(new SecurityToken(), $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->preRemove($args);
    }

    public function testPreRemoveDoesNothingWhenNoTokensFound(): void
    {
        $payment = new Payment();

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock();
        $em->shouldReceive('getRepository')->with(SecurityToken::class)->andReturn($repo);
        $repo->shouldReceive('findBy')->once()->with(['payment' => $payment])->andReturn([]);
        $em->shouldNotReceive('remove');

        $args = new PreRemoveEventArgs($payment, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->preRemove($args);
    }
}
