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
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Exception\InsufficientCreditException;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CreditRepository::class)]
final class CreditRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testAddCreditIncreasesBalance(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditRepository $repo */
        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 500);

        $this->em->refresh($client->getCredit());
        self::assertTrue($client->getCredit()->getValue()->isEqualTo(500));
    }

    public function testAddCreditIsAtomic(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditRepository $repo */
        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 300);
        $repo->addCredit($client, 200);

        $this->em->refresh($client->getCredit());
        self::assertTrue($client->getCredit()->getValue()->isEqualTo(500));
    }

    public function testDeductCreditDecreasesBalance(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditRepository $repo */
        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 1000);
        $repo->deductCredit($client, 400);

        $this->em->refresh($client->getCredit());
        self::assertTrue($client->getCredit()->getValue()->isEqualTo(600));
    }

    public function testDeductCreditThrowsWhenBalanceInsufficient(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditRepository $repo */
        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 100);

        $this->expectException(InsufficientCreditException::class);

        $repo->deductCredit($client, 500);
    }

    public function testDeductCreditThrowsOnZeroBalance(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditRepository $repo */
        $repo = $this->em->getRepository(Credit::class);

        $this->expectException(InsufficientCreditException::class);

        $repo->deductCredit($client, 1);
    }

    public function testDeductCreditAllowsFullBalance(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditRepository $repo */
        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 500);
        $repo->deductCredit($client, 500);

        $this->em->refresh($client->getCredit());
        self::assertTrue($client->getCredit()->getValue()->isEqualTo(BigInteger::zero()));
    }

    public function testDeductCreditDoesNotUpdateBalanceWhenInsufficient(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditRepository $repo */
        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 100);

        try {
            $repo->deductCredit($client, 500);
        } catch (InsufficientCreditException) {
            // expected
        }

        $this->em->refresh($client->getCredit());
        self::assertTrue(
            $client->getCredit()->getValue()->isEqualTo(100),
            'Balance must remain unchanged after a failed deduction',
        );
    }
}
