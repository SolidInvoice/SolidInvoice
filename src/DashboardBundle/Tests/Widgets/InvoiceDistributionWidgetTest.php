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
use SolidInvoice\DashboardBundle\Widgets\InvoiceDistributionWidget;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\UX\Chartjs\Model\Chart;

final class InvoiceDistributionWidgetTest extends WidgetTestCase
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
        $widget = self::getContainer()->get(InvoiceDistributionWidget::class);

        $data = $widget->getData();

        self::assertArrayHasKey('chart', $data);
        self::assertArrayHasKey('hasData', $data);
        self::assertArrayHasKey('total', $data);
        self::assertInstanceOf(Chart::class, $data['chart']);
    }

    public function testGetDataWithNoInvoices(): void
    {
        $widget = self::getContainer()->get(InvoiceDistributionWidget::class);

        $data = $widget->getData();

        self::assertFalse($data['hasData']);
        self::assertSame(0, $data['total']);
    }

    public function testGetDataWithInvoicesInDifferentStatuses(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        // Create invoices in different statuses
        InvoiceFactory::createMany(3, [
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(10000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        InvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(5000),
            'balance' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        InvoiceFactory::createMany(1, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'total' => BigInteger::of(7500),
            'balance' => BigInteger::of(7500),
            'baseTotal' => BigInteger::of(7500),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        InvoiceFactory::createMany(4, [
            'client' => $client,
            'status' => InvoiceStatus::Draft,
            'total' => BigInteger::of(2500),
            'balance' => BigInteger::of(2500),
            'baseTotal' => BigInteger::of(2500),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(InvoiceDistributionWidget::class);
        $data = $widget->getData();

        self::assertTrue($data['hasData']);
        self::assertSame(10, $data['total']); // 3 + 2 + 1 + 4
    }

    public function testChartContainsCorrectData(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany(5, [
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(10000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        InvoiceFactory::createMany(3, [
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(5000),
            'balance' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(InvoiceDistributionWidget::class);
        $data = $widget->getData();

        $chartData = $data['chart']->createView();

        // Verify the chart has datasets
        self::assertArrayHasKey('data', $chartData);
        self::assertArrayHasKey('datasets', $chartData['data']);
        self::assertNotEmpty($chartData['data']['datasets']);
    }

    public function testGetTemplate(): void
    {
        $widget = self::getContainer()->get(InvoiceDistributionWidget::class);

        self::assertSame('@SolidInvoiceDashboard/Widget/invoice_distribution.html.twig', $widget->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $widget = self::getContainer()->get(InvoiceDistributionWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testRenderWidgetWithData(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany(5, [
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(10000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        InvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(5000),
            'balance' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        InvoiceFactory::createMany(1, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'total' => BigInteger::of(7500),
            'balance' => BigInteger::of(7500),
            'baseTotal' => BigInteger::of(7500),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(InvoiceDistributionWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }
}
