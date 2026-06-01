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
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Payum\Core\Model\Identity;
use Payum\Core\Storage\IdentityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use SolidInvoice\PaymentBundle\Listener\Doctrine\SecurityTokenPaymentLinker;
use Symfony\Component\Uid\Ulid;

#[CoversClass(SecurityTokenPaymentLinker::class)]
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
        $listener->prePersist($token, $args);

        self::assertSame($payment, $token->getPayment());
    }

    public function testPrePersistIgnoresIdentityForNonPaymentClass(): void
    {
        $token = new SecurityToken();
        $token->setDetails(new Identity((string) new Ulid(), Client::class));

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');

        $args = new PrePersistEventArgs($token, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->prePersist($token, $args);

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
        $listener->prePersist($token, $args);

        self::assertNull($token->getPayment());
    }

    public function testPrePersistSkipsInvalidUlidString(): void
    {
        $token = new SecurityToken();
        $token->setDetails(new Identity('invalid', Payment::class));

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldNotReceive('getRepository');

        $args = new PrePersistEventArgs($token, $em);

        $listener = new SecurityTokenPaymentLinker();
        $listener->prePersist($token, $args);

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
        $listener->preUpdate($token, $args);

        self::assertSame($payment, $token->getPayment());
    }
}
