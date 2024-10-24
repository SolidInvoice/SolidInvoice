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

namespace SolidInvoice\PaymentBundle\Tests\Repository;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\Persistence\Mapping\MappingException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Model\Status;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use function date;
use function strtotime;

/** @covers \SolidInvoice\PaymentBundle\Repository\PaymentRepository */
final class PaymentRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use Factories;

    /**
     * @throws NotSupported
     */
    public function testGetTotalPaidForInvoice(): void
    {
        $client = ClientFactory::createOne();
        $invoice = InvoiceFactory::createOne(['client' => $client]);
        PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client,
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(1);

        self::assertTrue(
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalPaidForInvoice($invoice->_real())
                ->isEqualTo(500123)
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetTotalPaidForInvoiceWithNoCapturedPayments(): void
    {
        $client = ClientFactory::createOne();
        $invoice = InvoiceFactory::createOne(['client' => $client]);
        PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client,
            'totalAmount' => 500123,
            'status' => Status::STATUS_AUTHORIZED
        ]);

        PaymentFactory::assert()
            ->count(1);

        self::assertTrue(
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalPaidForInvoice($invoice->_real())
                ->isEqualTo(0)
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetTotalPaidForInvoiceWithDifferentInvoice(): void
    {
        $client = ClientFactory::createOne();
        $invoice = InvoiceFactory::createMany(2, ['client' => $client]);
        PaymentFactory::createOne([
            'invoice' => $invoice[0],
            'client' => $client,
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(1);

        self::assertTrue(
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalPaidForInvoice($invoice[1]->_real())
                ->isEqualTo(0)
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetTotalIncomeForClient(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        PaymentFactory::createOne([
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(1);

        self::assertTrue(
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncomeForClient($client->_real())
                ->isEqualTo(500123)
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetTotalIncomeForClientWithNoPayments(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        PaymentFactory::createOne([
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => ClientFactory::createOne(['currencyCode' => 'USD']),
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(1);

        self::assertTrue(
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncomeForClient($client->_real())
                ->isZero()
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetGridQuery(): void
    {
        $queryBuilder = $this
            ->em
            ->getRepository(Payment::class)
            ->getGridQuery();

        self::assertSame(
            'SELECT p, c, i, m FROM ' . Payment::class . ' p INNER JOIN p.client c INNER JOIN p.invoice i INNER JOIN p.method m',
            $queryBuilder->getDQL()
        );

        $client = new Client();

        $queryBuilder = $this
            ->em
            ->getRepository(Payment::class)
            ->getGridQuery(['client' => $client]);

        self::assertSame(
            'SELECT p, c, i, m FROM ' . Payment::class . ' p INNER JOIN p.client c INNER JOIN p.invoice i INNER JOIN p.method m WHERE p.client = :client',
            $queryBuilder->getDQL()
        );

        self::assertSame(
            $client,
            $queryBuilder->getParameter('client')->getValue()
        );

        $invoice = new Invoice();

        $queryBuilder = $this
            ->em
            ->getRepository(Payment::class)
            ->getGridQuery(['invoice' => $invoice]);

        self::assertSame(
            'SELECT p, c, i, m FROM ' . Payment::class . ' p INNER JOIN p.client c INNER JOIN p.invoice i INNER JOIN p.method m WHERE p.invoice = :invoice',
            $queryBuilder->getDQL()
        );

        self::assertSame(
            $invoice,
            $queryBuilder->getParameter('invoice')->getValue()
        );

        $invoice = new Invoice();
        $client = new Client();

        $queryBuilder = $this
            ->em
            ->getRepository(Payment::class)
            ->getGridQuery(['client' => $client, 'invoice' => $invoice]);

        self::assertSame(
            'SELECT p, c, i, m FROM ' . Payment::class . ' p INNER JOIN p.client c INNER JOIN p.invoice i INNER JOIN p.method m WHERE p.invoice = :invoice AND p.client = :client',
            $queryBuilder->getDQL()
        );

        self::assertSame(
            $client,
            $queryBuilder->getParameter('client')->getValue()
        );

        self::assertSame(
            $invoice,
            $queryBuilder->getParameter('invoice')->getValue()
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetPaymentsForClient(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        $created = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
        $completed = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));

        $invoice = InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-FOO'])->_disableAutoRefresh();
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client->_real(),
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED,
            'message' => 'test',
            'created' => $created,
            'completed' => $completed,
            'method' => PaymentMethodFactory::new(['name' => 'test-payment']),
        ]);

        self::assertEquals(
            [
                [
                    'id' => $payment->getId(),
                    'totalAmount' => 500123,
                    'currencyCode' => 'USD',
                    'created' => $created,
                    'completed' => $completed,
                    'status' => Status::STATUS_CAPTURED,
                    'invoice' => 'INV-FOO',
                    'method' => 'test-payment',
                    'message' => 'test',
                ]
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getPaymentsForClient($client->_real())
        );
    }

    /**
     * @throws NotSupported
     * @throws MathException
     */
    public function testGetTotalIncome(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        PaymentFactory::createMany(3, [
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::createMany(2, [
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => ClientFactory::createOne(['currencyCode' => 'EUR']),
            'currencyCode' => 'EUR',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(5);

        self::assertEquals(
            [
                'USD' => BigInteger::of(500123 * 3),
                'EUR' => BigInteger::of(500123 * 2),
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncome()
        );
    }

    /**
     * @throws MathException
     * @throws NotSupported
     */
    public function testGetTotalIncomeWithMultipleCurrencies(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        PaymentFactory::createMany(3, [
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        $client = ClientFactory::createOne(['currencyCode' => 'EUR']);

        PaymentFactory::createOne([
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => $client,
            'currencyCode' => 'EUR',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(4);

        self::assertEquals(
            [
                'USD' => BigInteger::of(500123 * 3),
                'EUR' => BigInteger::of(500123),
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncome()
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetPaymentsForInvoice(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        $invoice = InvoiceFactory::new(['client' => $client, 'invoiceId' => 'INV-FOO'])
            ->create()
            ->_disableAutoRefresh();

        $created = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
        $completed = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));

        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED,
            'message' => 'test',
            'created' => $created,
            'completed' => $completed,
            'method' => PaymentMethodFactory::createOne(['name' => 'test-payment']),
        ]);

        self::assertEquals(
            [
                [
                    'id' => $payment->getId(),
                    'totalAmount' => 500123,
                    'currencyCode' => 'USD',
                    'created' => $created,
                    'completed' => $completed,
                    'status' => Status::STATUS_CAPTURED,
                    'invoice' => 'INV-FOO',
                    'method' => 'test-payment',
                    'message' => 'test',
                ]
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getPaymentsForInvoice($invoice->_real())
        );
    }

    public function testGetPaymentsList(): void
    {
        $created = new DateTimeImmutable();

        PaymentFactory::createOne([
            'created' => $created,
            'totalAmount' => 500123,
        ]);

        self::assertSame(
            [
                [
                    strtotime($created->format('Y-m-d')) * 1000,
                    500123,
                ]
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getPaymentsList()
        );
    }

    /**
     * @throws NotSupported
     */
    public function testGetPaymentsByMonth(): void
    {
        $created = new DateTimeImmutable();

        PaymentFactory::createOne([
            'totalAmount' => 500123,
            'created' => $created,
        ]);

        self::assertEquals(
            [
                $created->format('F Y') => 500123,
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getPaymentsByMonth()
        );
    }

    /**
     * @throws MappingException
     * @throws NotSupported
     */
    public function testUpdatePaymentStatus(): void
    {
        /** @var Payment $payment */
        $payment = PaymentFactory::createOne([
            'status' => Status::STATUS_PENDING,
        ])->_real();

        $this
            ->em
            ->getRepository(Payment::class)
            ->updatePaymentStatus(new ArrayCollection([$payment]), Status::STATUS_CAPTURED);

        $this->em->clear();

        $payment = $this->em->getRepository(Payment::class)->find($payment->getId());

        self::assertSame(Status::STATUS_CAPTURED, $payment->getStatus());
    }

    /**
     * @throws NotSupported
     * @throws MathException
     */
    public function testGetRecentPayments(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = InvoiceFactory::new(['client' => $client, 'invoiceId' => 'INV-FOO'])
            ->create()
            ->_disableAutoRefresh();

        $created = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
        $completed = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));

        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED,
            'message' => 'test',
            'created' => $created,
            'completed' => $completed,
            'method' => PaymentMethodFactory::createOne(['name' => 'test-payment']),
        ]);

        self::assertEquals(
            [
                [
                    'id' => $payment->getId(),
                    'totalAmount' => 500123,
                    'currencyCode' => 'USD',
                    'created' => $created,
                    'completed' => $completed,
                    'status' => Status::STATUS_CAPTURED,
                    'invoice' => 'INV-FOO',
                    'method' => 'test-payment',
                    'client_id' => $client->getId(),
                    'client' => $client->getName(),
                    'message' => 'test',
                    'amount' => BigInteger::of(500123)
                ]
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getRecentPayments()
        );
    }
}
