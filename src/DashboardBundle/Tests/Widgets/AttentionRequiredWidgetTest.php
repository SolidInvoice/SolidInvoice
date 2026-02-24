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
use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\DashboardBundle\Widgets\AttentionRequiredWidget;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;

final class AttentionRequiredWidgetTest extends WidgetTestCase
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
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $data = $widget->getData();

        self::assertArrayHasKey('overdueInvoices', $data);
        self::assertArrayHasKey('draftInvoices', $data);
        self::assertArrayHasKey('pendingQuotes', $data);
        self::assertArrayHasKey('upcomingRecurring', $data);
        self::assertArrayHasKey('hasItems', $data);
    }

    public function testGetDataWithNoData(): void
    {
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $data = $widget->getData();

        self::assertSame([], $data['overdueInvoices']);
        self::assertSame([], $data['draftInvoices']);
        self::assertSame([], $data['pendingQuotes']);
        self::assertSame([], $data['upcomingRecurring']);
        self::assertFalse($data['hasItems']);
    }

    public function testGetDataWithOverdueInvoices(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany(3, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'due' => new DateTimeImmutable('-5 days'),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(3, $data['overdueInvoices']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataWithDraftInvoices(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => InvoiceStatus::Draft,
            'total' => BigInteger::of(5000),
            'balance' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(2, $data['draftInvoices']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataWithPendingQuotes(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        QuoteFactory::createMany(4, [
            'client' => $client,
            'company' => $this->company,
            'status' => QuoteStatus::Pending,
            'total' => BigInteger::of(7500),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(4, $data['pendingQuotes']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataWithUpcomingRecurring(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        RecurringInvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => RecurringInvoiceStatus::Active,
            'dateStart' => new DateTimeImmutable('+3 days'),
            'total' => BigInteger::of(15000),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(2, $data['upcomingRecurring']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataLimitsResults(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        // Create more than the limit (5)
        InvoiceFactory::createMany(10, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        // Should be limited to 5
        self::assertCount(5, $data['overdueInvoices']);
    }

    public function testGetTemplate(): void
    {
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        self::assertSame('@SolidInvoiceDashboard/Widget/attention_required.html.twig', $widget->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testRenderWidgetWithAllSections(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Test Client',
        ]);

        // Create overdue invoice
        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-001',
            'due' => new DateTimeImmutable('2024-01-15'),
        ]);

        // Create draft invoice
        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Draft,
            'total' => BigInteger::of(5000),
            'balance' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        // Create pending quote
        QuoteFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'status' => QuoteStatus::Pending,
            'total' => BigInteger::of(7500),
            'baseTotal' => BigInteger::of(7500),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'quoteId' => 'QUO-001',
            'created' => new \DateTime('2024-01-10'),
        ]);

        // Create upcoming recurring
        RecurringInvoiceFactory::createOne([
            'client' => $client,
            'status' => RecurringInvoiceStatus::Active,
            'dateStart' => new DateTimeImmutable('2024-01-20'),
            'total' => BigInteger::of(15000),
            'baseTotal' => BigInteger::of(15000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }
}
