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
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Exception\InsufficientCreditException;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

#[CoversClass(CreditRepository::class)]
final class CreditRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    private CreditRepository $creditRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = $this->em->getRepository(Credit::class);
        self::assertInstanceOf(CreditRepository::class, $repository);
        $this->creditRepository = $repository;
    }

    public function testAddCreditIncreasesBalance(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $client->getCredit()->setValue(BigInteger::of(500));
        $this->em->flush();

        $this->creditRepository->addCredit($client, 300);

        $this->em->clear();
        $clientId = $client->getId();
        self::assertInstanceOf(Ulid::class, $clientId);
        $refreshed = $this->em->find(Client::class, $clientId);
        self::assertNotNull($refreshed);
        self::assertTrue(
            BigInteger::of(800)->isEqualTo($refreshed->getCredit()->getValue()),
            'addCredit should atomically increase the balance'
        );
    }

    public function testDeductCreditDecreasesBalance(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $client->getCredit()->setValue(BigInteger::of(1000));
        $this->em->flush();

        $this->creditRepository->deductCredit($client, 400);

        $this->em->clear();
        $clientId = $client->getId();
        self::assertInstanceOf(Ulid::class, $clientId);
        $refreshed = $this->em->find(Client::class, $clientId);
        self::assertNotNull($refreshed);
        self::assertTrue(
            BigInteger::of(600)->isEqualTo($refreshed->getCredit()->getValue()),
            'deductCredit should atomically decrease the balance'
        );
    }

    public function testDeductCreditThrowsWhenInsufficientFunds(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $client->getCredit()->setValue(BigInteger::of(200));
        $this->em->flush();

        $this->expectException(InsufficientCreditException::class);

        $this->creditRepository->deductCredit($client, 500);
    }

    public function testDeductCreditExactBalanceSucceeds(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $client->getCredit()->setValue(BigInteger::of(500));
        $this->em->flush();

        $credit = $this->creditRepository->deductCredit($client, 500);

        self::assertTrue(
            BigInteger::of(0)->isEqualTo($credit->getValue()),
            'Deducting the exact available balance should succeed'
        );
    }
}
