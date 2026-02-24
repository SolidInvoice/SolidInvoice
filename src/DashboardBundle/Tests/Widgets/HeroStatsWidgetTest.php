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

namespace SolidInvoice\DashboardBundle\Tests\Widgets;

use Brick\Math\BigInteger;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\DashboardBundle\Widgets\HeroStatsWidget;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;

final class HeroStatsWidgetTest extends WidgetTestCase
{
    private function createZeroDiscount(): Discount
    {
        return (new Discount())
            ->setType('percentage')
            ->setValueMoney(BigInteger::zero())
            ->setValuePercentage(0);
    }

    public function testGetDataReturnsCorrectStructure(): void
    {
        $widget = self::getContainer()->get(HeroStatsWidget::class);

        $data = $widget->getData();

        self::assertArrayHasKey('totalOutstanding', $data);
        self::assertArrayHasKey('overdueCount', $data);
        self::assertArrayHasKey('overdueAmount', $data);
        self::assertArrayHasKey('paymentsThisMonth', $data);
        self::assertArrayHasKey('totalRevenue', $data);
    }

    public function testGetDataWithNoData(): void
    {
        $widget = self::getContainer()->get(HeroStatsWidget::class);

        $data = $widget->getData();

        self::assertSame([], $data['totalOutstanding']);
        self::assertSame(0, $data['overdueCount']);
        self::assertSame([], $data['overdueAmount']);
        self::assertSame([], $data['paymentsThisMonth']);
        self::assertSame([], $data['totalRevenue']);
    }

    public function testGetDataWithInvoices(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        // Create overdue invoices
        InvoiceFactory::createMany(3, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(10000), // $100.00
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'lines' => [
                (new Line())
                    ->setDescription('Test Item')
                    ->setQty(1)
                    ->setPrice(10000),
            ],
        ]);

        // Create pending invoices
        InvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'balance' => BigInteger::of(5000), // $50.00
            'total' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'lines' => [
                (new Line())
                    ->setDescription('Test Item')
                    ->setQty(1)
                    ->setPrice(5000),
            ],
        ]);

        $widget = self::getContainer()->get(HeroStatsWidget::class);
        $data = $widget->getData();

        self::assertSame(3, $data['overdueCount']);
        self::assertArrayHasKey('USD', $data['overdueAmount']);
        // Verify the overdue amount is 30000 (3 invoices * 10000)
        self::assertSame('30000', (string) $data['overdueAmount']['USD']);
        self::assertArrayHasKey('USD', $data['totalOutstanding']);
    }

    public function testGetDataWithPayments(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(50000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(50000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $paymentMethod = PaymentMethodFactory::createOne([
            'company' => $this->company,
        ]);

        PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $invoice,
            'method' => $paymentMethod,
            'totalAmount' => 50000, // $500.00
            'currencyCode' => 'USD',
            'status' => PaymentStatus::Captured,
            'created' => new \DateTime('now'),
        ]);

        $widget = self::getContainer()->get(HeroStatsWidget::class);
        $data = $widget->getData();

        self::assertArrayHasKey('USD', $data['totalRevenue']);
        self::assertSame('50000', (string) $data['totalRevenue']['USD']);
    }

    public function testGetTemplate(): void
    {
        $widget = self::getContainer()->get(HeroStatsWidget::class);

        self::assertSame('@SolidInvoiceDashboard/Widget/hero_stats.html.twig', $widget->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $widget = self::getContainer()->get(HeroStatsWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testRenderWidgetWithData(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        // Create overdue invoices
        InvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(15000),
            'total' => BigInteger::of(15000),
            'baseTotal' => BigInteger::of(15000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        // Create pending invoices
        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(25000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(25000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $paymentMethod = PaymentMethodFactory::createOne([
            'company' => $this->company,
        ]);

        PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $invoice,
            'method' => $paymentMethod,
            'totalAmount' => 25000,
            'currencyCode' => 'USD',
            'status' => PaymentStatus::Captured,
            'created' => new \DateTime('now'),
        ]);

        $widget = self::getContainer()->get(HeroStatsWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }
}
