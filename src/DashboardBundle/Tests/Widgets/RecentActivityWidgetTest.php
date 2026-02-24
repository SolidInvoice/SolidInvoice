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
use SolidInvoice\DashboardBundle\Widgets\RecentActivityWidget;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;

final class RecentActivityWidgetTest extends WidgetTestCase
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
        $widget = self::getContainer()->get(RecentActivityWidget::class);

        $data = $widget->getData();

        self::assertArrayHasKey('activities', $data);
        self::assertArrayHasKey('hasActivities', $data);
    }

    public function testGetDataWithNoData(): void
    {
        $widget = self::getContainer()->get(RecentActivityWidget::class);

        $data = $widget->getData();

        self::assertSame([], $data['activities']);
        self::assertFalse($data['hasActivities']);
    }

    public function testGetDataWithPayments(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Test Client',
        ]);

        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(50000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(50000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-001',
        ]);

        $paymentMethod = PaymentMethodFactory::createOne([
            'company' => $this->company,
        ]);

        PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $invoice,
            'method' => $paymentMethod,
            'totalAmount' => 50000,
            'currencyCode' => 'USD',
            'status' => PaymentStatus::Captured,
            'created' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);
        $data = $widget->getData();

        self::assertTrue($data['hasActivities']);
        self::assertNotEmpty($data['activities']);

        // Find the payment activity
        $paymentActivity = array_filter($data['activities'], fn ($a) => $a['type'] === 'payment');
        self::assertNotEmpty($paymentActivity);

        $payment = current($paymentActivity);
        self::assertSame('payment', $payment['type']);
        self::assertSame('Test Client', $payment['client']);
        self::assertSame('INV-001', $payment['invoiceId']);
    }

    public function testGetDataWithSentInvoices(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Test Client',
        ]);

        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(25000),
            'balance' => BigInteger::of(25000),
            'baseTotal' => BigInteger::of(25000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-002',
            'updated' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);
        $data = $widget->getData();

        self::assertTrue($data['hasActivities']);

        // Find the invoice_sent activity
        $invoiceSentActivity = array_filter($data['activities'], fn ($a) => $a['type'] === 'invoice_sent');
        self::assertNotEmpty($invoiceSentActivity);

        $invoice = current($invoiceSentActivity);
        self::assertSame('invoice_sent', $invoice['type']);
        self::assertSame('Test Client', $invoice['client']);
        self::assertSame('INV-002', $invoice['invoiceId']);
    }

    public function testGetDataWithAcceptedQuotes(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Test Client',
        ]);

        QuoteFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'status' => QuoteStatus::Accepted,
            'total' => BigInteger::of(15000),
            'baseTotal' => BigInteger::of(15000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'quoteId' => 'QUO-001',
            'updated' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);
        $data = $widget->getData();

        self::assertTrue($data['hasActivities']);

        // Find the quote_accepted activity
        $quoteAcceptedActivity = array_filter($data['activities'], fn ($a) => $a['type'] === 'quote_accepted');
        self::assertNotEmpty($quoteAcceptedActivity);

        $quote = current($quoteAcceptedActivity);
        self::assertSame('quote_accepted', $quote['type']);
        self::assertSame('Test Client', $quote['client']);
        self::assertSame('QUO-001', $quote['quoteId']);
    }

    public function testGetDataWithDeclinedQuotes(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Test Client',
        ]);

        QuoteFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'status' => QuoteStatus::Declined,
            'total' => BigInteger::of(12000),
            'baseTotal' => BigInteger::of(12000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'quoteId' => 'QUO-002',
            'updated' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);
        $data = $widget->getData();

        self::assertTrue($data['hasActivities']);

        // Find the quote_declined activity
        $quoteDeclinedActivity = array_filter($data['activities'], fn ($a) => $a['type'] === 'quote_declined');
        self::assertNotEmpty($quoteDeclinedActivity);

        $quote = current($quoteDeclinedActivity);
        self::assertSame('quote_declined', $quote['type']);
    }

    public function testGetDataWithRecurringGeneratedInvoices(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Test Client',
        ]);

        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'client' => $client,
            'status' => RecurringInvoiceStatus::Active,
            'total' => BigInteger::of(30000),
            'baseTotal' => BigInteger::of(30000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(30000),
            'balance' => BigInteger::of(30000),
            'baseTotal' => BigInteger::of(30000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-REC-001',
            'recurringInvoice' => $recurringInvoice,
            'created' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);
        $data = $widget->getData();

        self::assertTrue($data['hasActivities']);

        // Find the recurring_generated activity
        $recurringActivity = array_filter($data['activities'], fn ($a) => $a['type'] === 'recurring_generated');
        self::assertNotEmpty($recurringActivity);

        $recurring = current($recurringActivity);
        self::assertSame('recurring_generated', $recurring['type']);
        self::assertSame('INV-REC-001', $recurring['invoiceId']);
    }

    public function testActivitiesAreSortedByDateDescending(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        // Create activities at different times
        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(10000),
            'balance' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-OLD',
            'updated' => new DateTime('-2 days'),
        ]);

        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(20000),
            'balance' => BigInteger::of(20000),
            'baseTotal' => BigInteger::of(20000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-NEW',
            'updated' => new DateTime('now'),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);
        $data = $widget->getData();

        self::assertCount(2, $data['activities']);
        self::assertSame('INV-NEW', $data['activities'][0]['invoiceId']);
        self::assertSame('INV-OLD', $data['activities'][1]['invoiceId']);
    }

    public function testActivitiesAreLimitedTo10(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        // Create more than 10 activities
        InvoiceFactory::createMany(15, [
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(10000),
            'balance' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);
        $data = $widget->getData();

        // Activities from sent invoices should be limited
        self::assertLessThanOrEqual(10, count($data['activities']));
    }

    public function testGetTemplate(): void
    {
        $widget = self::getContainer()->get(RecentActivityWidget::class);

        self::assertSame('@SolidInvoiceDashboard/Widget/recent_activity.html.twig', $widget->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $widget = self::getContainer()->get(RecentActivityWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testRenderWidgetWithMixedActivities(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Acme Corp',
        ]);

        // Payment
        $paidInvoice = InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of(50000),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of(50000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-001',
        ]);

        $paymentMethod = PaymentMethodFactory::createOne([
            'company' => $this->company,
        ]);

        PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $paidInvoice,
            'method' => $paymentMethod,
            'totalAmount' => 50000,
            'currencyCode' => 'USD',
            'status' => PaymentStatus::Captured,
            'created' => new DateTime('-1 hour'),
        ]);

        // Sent invoice
        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'total' => BigInteger::of(25000),
            'balance' => BigInteger::of(25000),
            'baseTotal' => BigInteger::of(25000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-002',
            'updated' => new DateTime('-2 hours'),
        ]);

        // Accepted quote
        QuoteFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'status' => QuoteStatus::Accepted,
            'total' => BigInteger::of(15000),
            'baseTotal' => BigInteger::of(15000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'quoteId' => 'QUO-001',
            'updated' => new DateTime('-3 hours'),
        ]);

        $widget = self::getContainer()->get(RecentActivityWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }
}
