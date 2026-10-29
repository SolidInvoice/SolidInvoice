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

namespace SolidInvoice\ApiBundle\Tests\Functional\Api;

use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\DashboardBundle\Widgets\HeroStatsWidget;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use function array_column;

#[Group('functional')]
final class DashboardTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testTheDashboardSumsPerCurrencyAndListsWhatNeedsAttention(): void
    {
        $zar = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'ZAR', 'name' => 'Smith']);
        $jpy = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'JPY', 'name' => 'Tanaka']);

        $this->invoice($zar, InvoiceStatus::Overdue, 120000, ['invoiceId' => 'INV-1', 'due' => CarbonImmutable::today()->subDays(12)]);
        $this->invoice($zar, InvoiceStatus::Pending, 30050);
        $this->invoice($jpy, InvoiceStatus::Pending, 5000);
        $this->invoice($zar, InvoiceStatus::Draft, 100, ['invoiceId' => 'INV-3']);
        QuoteFactory::createOne(['client' => $zar, 'status' => QuoteStatus::Pending, 'quoteId' => 'Q-1', 'due' => CarbonImmutable::today()->addDays(2)]);
        PaymentFactory::createOne([
            'client' => $zar,
            'invoice' => $this->invoice($zar, InvoiceStatus::Paid, 26100),
            'method' => PaymentMethodFactory::createOne(['company' => $this->company]),
            'totalAmount' => 26100,
            'currencyCode' => 'ZAR',
            'status' => PaymentStatus::Captured,
            'completed' => CarbonImmutable::now(),
        ]);

        $dashboard = self::$client->request('GET', '/api/dashboard')->toArray();

        $outstanding = array_column($dashboard['outstanding'], null, 'currency');
        self::assertSame(['currency' => 'ZAR', 'amount' => '1500.50', 'count' => 2], $outstanding['ZAR']);
        self::assertSame(['currency' => 'JPY', 'amount' => '5000', 'count' => 1], $outstanding['JPY']);
        self::assertSame([['currency' => 'ZAR', 'amount' => '1200.00', 'count' => 1]], $dashboard['overdue']);
        self::assertSame([['currency' => 'ZAR', 'amount' => '261.00', 'count' => 1]], $dashboard['paidThisMonth']);
        self::assertSame(1, $dashboard['draftInvoices']);
        self::assertSame(1, $dashboard['pendingQuotes']);

        $attention = array_column($dashboard['needsAttention'], null, 'type');
        self::assertSame('INV-1 · Smith', $attention['invoice_overdue']['label']);
        self::assertSame(12, $attention['invoice_overdue']['daysOverdue']);
        self::assertSame('1200.00', $attention['invoice_overdue']['amount']);
        self::assertSame(2, $attention['quote_expiring']['daysLeft']);
        self::assertSame('INV-3 · Smith', $attention['invoice_draft']['label']);
        self::assertSame(['month' => CarbonImmutable::now()->format('Y-m'), 'currency' => 'ZAR', 'amount' => '261.00'], $dashboard['revenueByMonth'][0]);

        // The web dashboard reads the same figures, in minor units.
        $widget = self::getContainer()->get(HeroStatsWidget::class)->getData();
        self::assertSame('150050', (string) $widget['totalOutstanding']['ZAR']);
        self::assertSame('26100', (string) $widget['paymentsThisMonth']['ZAR']);
    }

    /**
     * Totals are recalculated from the lines on save, so the line carries the amount.
     *
     * @param array<string, mixed> $attributes
     */
    private function invoice(Client $client, InvoiceStatus $status, int $amount, array $attributes = []): Invoice
    {
        return InvoiceFactory::createOne([
            'client' => $client,
            'status' => $status,
            'total' => BigInteger::of($amount),
            'baseTotal' => BigInteger::of($amount),
            'balance' => BigInteger::of($status === InvoiceStatus::Paid ? 0 : $amount),
            'tax' => BigInteger::zero(),
            'discount' => new Discount()->setType('percentage')->setValueMoney(BigInteger::zero())->setValuePercentage(0),
            'lines' => [new Line()->setDescription('Work')->setQty(1)->setPrice($amount)],
            ...$attributes,
        ]);
    }
}
