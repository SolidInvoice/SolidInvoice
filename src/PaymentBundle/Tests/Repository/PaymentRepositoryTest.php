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

use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Money\Currency;
use Money\Money;
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

        self::assertSame(
            500123,
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalPaidForInvoice($invoice->object())
        );
    }

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

        self::assertSame(
            0,
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalPaidForInvoice($invoice->object())
        );
    }

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

        self::assertSame(
            0,
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalPaidForInvoice($invoice[1]->object())
        );
    }

    public function testGetTotalIncomeForClient(): void
    {
        $client = ClientFactory::createOne(['currency' => 'USD']);

        PaymentFactory::createOne([
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(1);

        self::assertEquals(
            new Money(
                500123,
                new Currency('USD')
            ),
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncomeForClient($client->object())
        );
    }

    public function testGetTotalIncomeForClientWithNoPayments(): void
    {
        $client = ClientFactory::createOne(['currency' => 'USD']);

        PaymentFactory::createOne([
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => ClientFactory::createOne(['currency' => 'USD']),
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(1);

        self::assertNull(
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncomeForClient($client->object())
        );
    }

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
            $client->getId(),
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
            $invoice->getId(),
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
            $client->getId(),
            $queryBuilder->getParameter('client')->getValue()
        );

        self::assertSame(
            $invoice->getId(),
            $queryBuilder->getParameter('invoice')->getValue()
        );
    }

    public function testGetPaymentsForClient(): void
    {
        $client = ClientFactory::createOne(['currency' => 'USD']);

        $created = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));
        $completed = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));

        $invoice = InvoiceFactory::createOne(['client' => $client, 'archived' => null])->disableAutoRefresh();
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client->object(),
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
                    'invoice' => $invoice->object()->getId(),
                    'method' => 'test-payment',
                    'message' => 'test',
                ]
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getPaymentsForClient($client->object())
        );
    }

    public function testGetTotalIncome(): void
    {
        $client = ClientFactory::createOne(['currency' => 'USD']);

        PaymentFactory::createMany(3, [
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        PaymentFactory::assert()
            ->count(3);

        self::assertEquals(
            [
                new Money(
                    500123 * 3,
                    new Currency('USD')
                ),
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncome()
        );
    }

    public function testGetTotalIncomeWithMultipleCurrencies(): void
    {
        $client = ClientFactory::createOne(['currency' => 'USD']);

        PaymentFactory::createMany(3, [
            'invoice' => InvoiceFactory::new(['client' => $client]),
            'client' => $client,
            'currencyCode' => 'USD',
            'totalAmount' => 500123,
            'status' => Status::STATUS_CAPTURED
        ]);

        $client = ClientFactory::createOne(['currency' => 'EUR']);

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
                new Money(
                    500123 * 3,
                    new Currency('USD')
                ),
                new Money(
                    500123,
                    new Currency('EUR')
                ),
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getTotalIncome()
        );
    }

    public function testGetPaymentsForInvoice(): void
    {
        $client = ClientFactory::createOne(['currency' => 'USD']);

        $invoice = InvoiceFactory::new(['client' => $client, 'archived' => null])
            ->create()
            ->disableAutoRefresh();

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
                    'invoice' => $invoice->object()->getId(),
                    'method' => 'test-payment',
                    'message' => 'test',
                ]
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getPaymentsForInvoice($invoice->object())
        );
    }

    public function testGetPaymentsList(): void
    {
        $created = new DateTimeImmutable();

        PaymentFactory::createOne([
            'created' => $created,
            'totalAmount' => 500123,
        ]);

        self::assertEquals(
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

    public function testUpdateCurrency(): void
    {
        $client = ClientFactory::createOne()->object();

        PaymentFactory::createMany(3, [
            'client' => $client,
        ]);

        $client->setCurrency('EUR');

        $this
            ->em
            ->getRepository(Payment::class)
            ->updateCurrency($client);

        $this->em->clear();

        $payments = $this->em->getRepository(Payment::class)->findAll();

        foreach ($payments as $payment) {
            self::assertSame('EUR', $payment->getCurrencyCode());
        }
    }

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

    public function testUpdatePaymentStatus(): void
    {
        /** @var Payment $payment */
        $payment = PaymentFactory::createOne([
            'status' => Status::STATUS_PENDING,
        ])->object();

        $this
            ->em
            ->getRepository(Payment::class)
            ->updatePaymentStatus(new ArrayCollection([$payment]), Status::STATUS_CAPTURED);

        $this->em->clear();

        $payment = $this->em->getRepository(Payment::class)->find($payment->getId());

        self::assertSame(Status::STATUS_CAPTURED, $payment->getStatus());
    }

    public function testGetRecentPayments(): void
    {
        $client = ClientFactory::createOne(['currency' => 'USD', 'archived' => null]);
        $invoice = InvoiceFactory::new(['client' => $client, 'archived' => null])
            ->create()
            ->disableAutoRefresh();

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
                    'invoice' => $invoice->object()->getId(),
                    'method' => 'test-payment',
                    'client_id' => $client->getId(),
                    'client' => $client->getName(),
                    'message' => 'test',
                    'amount' => new Money(500123, new Currency('USD'))
                ]
            ],
            $this
                ->em
                ->getRepository(Payment::class)
                ->getRecentPayments()
        );
    }
}
