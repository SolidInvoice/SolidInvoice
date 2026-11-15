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
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $repo = $this->em->getRepository(Credit::class);

        $credit = $repo->addCredit($client, 500);

        self::assertTrue(BigInteger::of(500)->isEqualTo($credit->getValue()));
    }

    public function testAddCreditAccumulatesMultipleAdditions(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 300);

        $credit = $repo->addCredit($client, 200);

        self::assertTrue(BigInteger::of(500)->isEqualTo($credit->getValue()));
    }

    public function testDeductCreditReducesBalance(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 1000);

        $credit = $repo->deductCredit($client, 400);

        self::assertTrue(BigInteger::of(600)->isEqualTo($credit->getValue()));
    }

    public function testDeductCreditExactBalanceSucceeds(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $repo = $this->em->getRepository(Credit::class);

        $repo->addCredit($client, 500);

        $credit = $repo->deductCredit($client, 500);

        self::assertTrue(BigInteger::zero()->isEqualTo($credit->getValue()));
    }

    public function testDeductCreditThrowsWhenBalanceIsInsufficient(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $this->expectException(InsufficientCreditException::class);

        $this->em->getRepository(Credit::class)
            ->deductCredit($client, 100);
    }

    public function testDeductCreditThrowsWhenDeductionExceedsBalance(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $repo = $this->em->getRepository(Credit::class);
        $repo->addCredit($client, 200);

        $this->expectException(InsufficientCreditException::class);

        $repo->deductCredit($client, 300);
    }
}
