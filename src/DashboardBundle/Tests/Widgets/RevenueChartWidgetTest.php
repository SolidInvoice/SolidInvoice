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
use DateTime;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\DashboardBundle\Widgets\RevenueChartWidget;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\UX\Chartjs\Model\Chart;

final class RevenueChartWidgetTest extends WidgetTestCase
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
        $widget = self::getContainer()->get(RevenueChartWidget::class);

        $data = $widget->getData();

        self::assertArrayHasKey('chart', $data);
        self::assertArrayHasKey('hasData', $data);
        self::assertInstanceOf(Chart::class, $data['chart']);
    }

    public function testGetDataWithNoPayments(): void
    {
        $widget = self::getContainer()->get(RevenueChartWidget::class);

        $data = $widget->getData();

        self::assertFalse($data['hasData']);
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

        // Create payments for the current month
        PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $invoice,
            'method' => $paymentMethod,
            'totalAmount' => 50000,
            'currencyCode' => 'USD',
            'status' => PaymentStatus::Captured,
            'created' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RevenueChartWidget::class);
        $data = $widget->getData();

        self::assertTrue($data['hasData']);
    }

    public function testChartContainsLast12MonthsLabels(): void
    {
        $widget = self::getContainer()->get(RevenueChartWidget::class);
        $data = $widget->getData();

        $chartData = $data['chart']->createView();

        self::assertArrayHasKey('data', $chartData);
        self::assertArrayHasKey('labels', $chartData['data']);
        self::assertCount(12, $chartData['data']['labels']);
    }

    public function testChartContainsDatasets(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
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
            'created' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RevenueChartWidget::class);
        $data = $widget->getData();

        $chartData = $data['chart']->createView();

        self::assertArrayHasKey('datasets', $chartData['data']);
        self::assertNotEmpty($chartData['data']['datasets']);

        // First dataset should have USD label
        $firstDataset = $chartData['data']['datasets'][0];
        self::assertSame('USD', $firstDataset['label']);
        self::assertCount(12, $firstDataset['data']);
    }

    public function testGetTemplate(): void
    {
        $widget = self::getContainer()->get(RevenueChartWidget::class);

        self::assertSame('@SolidInvoiceDashboard/Widget/revenue_chart.html.twig', $widget->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $widget = self::getContainer()->get(RevenueChartWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testRenderWidgetWithData(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(100000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(100000),
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
            'totalAmount' => 100000,
            'currencyCode' => 'USD',
            'status' => PaymentStatus::Captured,
            'created' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RevenueChartWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }
}
