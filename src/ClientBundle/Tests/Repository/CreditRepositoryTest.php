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

namespace SolidInvoice\ClientBundle\Tests\Repository;

use Brick\Math\BigInteger;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Exception\InsufficientCreditException;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('functional')]
final class CreditRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    private CreditRepository $creditRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creditRepository = $this->em->getRepository(Credit::class);
    }

    public function testAddCreditIsAtomic(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        $credit = $this->creditRepository->addCredit($client, 50000);

        self::assertSame('50000', (string) $credit->getValue());

        // Reload from DB to confirm the value was actually persisted
        $this->em->clear();
        $reloaded = $this->em->find(Client::class, $client->getId());
        self::assertInstanceOf(Client::class, $reloaded);
        self::assertSame('50000', (string) $reloaded->getCredit()->getValue());
    }

    public function testDeductCreditIsAtomicAndUpdatesBalance(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        // Start at 50000 (e.g. $500.00)
        $credit = $client->getCredit();
        $credit->setValue(BigInteger::of(50000));
        $this->em->persist($credit);
        $this->em->flush();

        $result = $this->creditRepository->deductCredit($client, 20000);

        self::assertSame('30000', (string) $result->getValue());

        // Confirm the DB was updated
        $this->em->clear();
        $reloaded = $this->em->find(Client::class, $client->getId());
        self::assertInstanceOf(Client::class, $reloaded);
        self::assertSame('30000', (string) $reloaded->getCredit()->getValue());
    }

    public function testDeductCreditExactBalanceSucceeds(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        $credit = $client->getCredit();
        $credit->setValue(BigInteger::of(50000));
        $this->em->persist($credit);
        $this->em->flush();

        $result = $this->creditRepository->deductCredit($client, 50000);

        self::assertSame('0', (string) $result->getValue());
    }

    public function testDeductCreditThrowsWhenBalanceIsZero(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        $this->expectException(InsufficientCreditException::class);

        // Balance starts at 0; deduction of any positive amount must be rejected
        $this->creditRepository->deductCredit($client, 1);
    }

    public function testDeductCreditThrowsWhenAmountExceedsBalance(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        $credit = $client->getCredit();
        $credit->setValue(BigInteger::of(10000));
        $this->em->persist($credit);
        $this->em->flush();

        $this->expectException(InsufficientCreditException::class);

        $this->creditRepository->deductCredit($client, 50000);
    }
}
