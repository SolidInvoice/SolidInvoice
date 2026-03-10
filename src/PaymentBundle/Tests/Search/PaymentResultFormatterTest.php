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

namespace SolidInvoice\PaymentBundle\Tests\Search;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\PaymentBundle\Search\PaymentResultFormatter;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Routing\RouterInterface;

final class PaymentResultFormatterTest extends TestCase
{
    private MockObject&RouterInterface $router;

    private MockObject&MoneyFormatterInterface $moneyFormatter;

    private MockObject&SystemConfig $systemConfig;

    private PaymentResultFormatter $formatter;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->moneyFormatter = $this->createMock(MoneyFormatterInterface::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->formatter = new PaymentResultFormatter($this->router, $this->moneyFormatter, $this->systemConfig);
    }

    public function testImplementsResultFormatterInterface(): void
    {
        self::assertInstanceOf(ResultFormatterInterface::class, $this->formatter);
    }

    public function testImplementsQualifiedResultFormatterInterface(): void
    {
        self::assertInstanceOf(QualifiedResultFormatterInterface::class, $this->formatter);
    }

    public function testGetIndexNameReturnsPayments(): void
    {
        self::assertSame('payments', $this->formatter->getIndexName());
    }

    public function testGetSupportedQualifiersReturnsExpectedMapping(): void
    {
        self::assertSame([
            'status' => 'status',
            'client' => 'client.name',
            'amount' => 'total',
        ], $this->formatter->getSupportedQualifiers());
    }

    public function testFormatWithReferenceUsesReferenceAsTitle(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');

        $hit = [
            'id' => 'pay-1',
            'reference' => 'TXN-12345',
            'invoice' => ['invoiceId' => 'INV-001', 'id' => 'inv-1'],
            'client' => ['name' => 'Acme Corp'],
            'status' => 'captured',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('payment', $result->type);
        self::assertSame('pay-1', $result->id);
        self::assertSame('TXN-12345', $result->title);
    }

    public function testFormatWithoutReferenceUsesInvoiceRefAsTitle(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');

        $hit = [
            'id' => 'pay-1',
            'invoice' => ['invoiceId' => 'INV-001', 'id' => 'inv-1'],
            'client' => ['name' => 'Acme Corp'],
            'status' => 'captured',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('INV-001', $result->title);
    }

    public function testFormatWithoutReferenceOrInvoiceRefUsesIdAsTitle(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $result = $this->formatter->format(['id' => 'pay-1']);

        self::assertSame('pay-1', $result->title);
    }

    public function testFormatSubtitleShowsClientAndInvoiceRefWhenBothPresent(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');

        $hit = [
            'id' => 'pay-1',
            'invoice' => ['invoiceId' => 'INV-001', 'id' => 'inv-1'],
            'client' => ['name' => 'Acme Corp'],
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('Acme Corp — INV-001', $result->subtitle);
    }

    public function testFormatSubtitleShowsOnlyClientWhenNoInvoice(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $hit = [
            'id' => 'pay-1',
            'client' => ['name' => 'Acme Corp'],
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('Acme Corp', $result->subtitle);
    }

    public function testFormatSubtitleShowsOnlyClientWhenInvoiceRefMissing(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $hit = ['id' => 'pay-1', 'client' => ['name' => 'Acme Corp']];

        $result = $this->formatter->format($hit);

        self::assertSame('Acme Corp', $result->subtitle);
    }

    public function testFormatSubtitleIsEmptyWhenNeitherClientNorInvoiceRefPresent(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $result = $this->formatter->format(['id' => 'pay-1']);

        self::assertSame('', $result->subtitle);
    }

    public function testFormatLinksToInvoiceViewWhenInvoiceIdPresent(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', ['id' => 'inv-99'])
            ->willReturn('/invoices/inv-99');

        $this->formatter->format(['id' => 'pay-1', 'invoice' => ['id' => 'inv-99']]);
    }

    public function testFormatLinksToPaymentsIndexWhenNoInvoiceId(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('_payments_index')
            ->willReturn('/payments');

        $this->formatter->format(['id' => 'pay-1']);
    }

    public function testFormatWithStatus(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $result = $this->formatter->format(['id' => 'pay-1', 'status' => 'refunded']);

        self::assertSame('refunded', $result->status);
    }

    public function testFormatWithMissingStatusResultsInNull(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $result = $this->formatter->format(['id' => 'pay-1']);

        self::assertNull($result->status);
    }

    public function testFormatMetaWithAmount(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $this->moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(25000, new Currency('USD')))
            ->willReturn('$250.00');

        $hit = ['id' => 'pay-1', 'total' => '250.00', 'currencyCode' => 'USD'];

        $result = $this->formatter->format($hit);

        self::assertSame('$250.00', $result->meta);
    }

    public function testFormatMetaIsNullWhenTotalMissing(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $result = $this->formatter->format(['id' => 'pay-1']);

        self::assertNull($result->meta);
    }

    public function testFormatFallsBackToSystemCurrencyWhenCurrencyCodeMissing(): void
    {
        $this->router->method('generate')->willReturn('/payments');
        $this->systemConfig->method('getCurrency')->willReturn(new Currency('EUR'));

        $this->moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(10000, new Currency('EUR')))
            ->willReturn('€100.00');

        $hit = ['id' => 'pay-1', 'total' => '100.00'];

        $result = $this->formatter->format($hit);

        self::assertSame('€100.00', $result->meta);
    }

    public function testFormatConvertsAmountFromMajorToMinorUnits(): void
    {
        $this->router->method('generate')->willReturn('/payments');

        $this->moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(99, new Currency('USD')))
            ->willReturn('$0.99');

        $hit = ['id' => 'pay-1', 'total' => '0.99', 'currencyCode' => 'USD'];

        $result = $this->formatter->format($hit);

        self::assertSame('$0.99', $result->meta);
    }
}
