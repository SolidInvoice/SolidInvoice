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
use Brick\Math\Exception\MathException;
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

    private CreditRepository $creditRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $creditRepository = $this->em->getRepository(Credit::class);
        assert($creditRepository instanceof CreditRepository);
        $this->creditRepository = $creditRepository;
    }

    /**
     * @throws MathException
     */
    public function testAddCreditIncreasesBalance(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $credit = $client->getCredit();
        $credit->setValue(50000);
        $this->em->flush();

        $returned = $this->creditRepository->addCredit($client, 20000);

        self::assertTrue(BigInteger::of(70000)->isEqualTo($returned->getValue()));

        $this->em->refresh($credit);
        self::assertTrue(BigInteger::of(70000)->isEqualTo($credit->getValue()));
    }

    /**
     * @throws MathException
     */
    public function testDeductCreditDecreasesBalance(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $credit = $client->getCredit();
        $credit->setValue(50000);
        $this->em->flush();

        $returned = $this->creditRepository->deductCredit($client, 20000);

        self::assertTrue(BigInteger::of(30000)->isEqualTo($returned->getValue()));

        $this->em->refresh($credit);
        self::assertTrue(BigInteger::of(30000)->isEqualTo($credit->getValue()));
    }

    /**
     * @throws MathException
     */
    public function testDeductCreditExactAmountSucceeds(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $credit = $client->getCredit();
        $credit->setValue(50000);
        $this->em->flush();

        $returned = $this->creditRepository->deductCredit($client, 50000);

        self::assertTrue(BigInteger::of(0)->isEqualTo($returned->getValue()));
    }

    /**
     * @throws MathException
     */
    public function testDeductCreditThrowsWhenInsufficientBalance(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $credit = $client->getCredit();
        $credit->setValue(50000);
        $this->em->flush();

        $this->expectException(InsufficientCreditException::class);

        $this->creditRepository->deductCredit($client, 50001);
    }

    /**
     * @throws MathException
     */
    public function testDeductCreditThrowsWhenBalanceIsZero(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);

        $this->expectException(InsufficientCreditException::class);

        $this->creditRepository->deductCredit($client, 1);
    }

    /**
     * @throws MathException
     */
    public function testConcurrentDeductionsDoNotAllowOverdraft(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $credit = $client->getCredit();
        $credit->setValue(50000);
        $this->em->flush();

        $this->creditRepository->deductCredit($client, 50000);

        $this->expectException(InsufficientCreditException::class);

        $this->creditRepository->deductCredit($client, 1);
    }
}
