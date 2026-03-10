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

namespace SolidInvoice\InvoiceBundle\Tests\Search;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\InvoiceBundle\Search\InvoiceResultFormatter;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Routing\RouterInterface;

final class InvoiceResultFormatterTest extends TestCase
{
    private MockObject&RouterInterface $router;

    private MockObject&MoneyFormatterInterface $moneyFormatter;

    private MockObject&SystemConfig $systemConfig;

    private InvoiceResultFormatter $formatter;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->moneyFormatter = $this->createMock(MoneyFormatterInterface::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->formatter = new InvoiceResultFormatter($this->router, $this->moneyFormatter, $this->systemConfig);
    }

    public function testImplementsResultFormatterInterface(): void
    {
        self::assertInstanceOf(ResultFormatterInterface::class, $this->formatter);
    }

    public function testImplementsQualifiedResultFormatterInterface(): void
    {
        self::assertInstanceOf(QualifiedResultFormatterInterface::class, $this->formatter);
    }

    public function testGetIndexNameReturnsInvoices(): void
    {
        self::assertSame('invoices', $this->formatter->getIndexName());
    }

    public function testGetSupportedQualifiersReturnsExpectedMapping(): void
    {
        self::assertSame([
            'status' => 'status',
            'amount' => 'total',
            'client' => 'client.name',
            'created' => 'created',
        ], $this->formatter->getSupportedQualifiers());
    }

    public function testFormatMapsHitToSearchResult(): void
    {
        $this->router
            ->method('generate')
            ->with('_invoices_view', ['id' => 'inv-1'])
            ->willReturn('/invoices/inv-1');

        $this->moneyFormatter->method('format')->willReturn('$1,500.00');

        $hit = [
            'id' => 'inv-1',
            'invoiceId' => 'INV-001',
            'client' => ['name' => 'Acme Corp', 'currencyCode' => 'USD'],
            'status' => 'paid',
            'total' => '1500.00',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('invoice', $result->type);
        self::assertSame('inv-1', $result->id);
        self::assertSame('INV-001', $result->title);
        self::assertSame('Acme Corp', $result->subtitle);
        self::assertSame('/invoices/inv-1', $result->url);
        self::assertSame('paid', $result->status);
        self::assertSame('$1,500.00', $result->meta);
    }

    public function testFormatUsesIdAsTitleWhenInvoiceIdMissing(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');
        $this->moneyFormatter->method('format')->willReturn('$0.00');

        $hit = ['id' => 'inv-1', 'total' => '0.00', 'client' => ['currencyCode' => 'USD']];

        $result = $this->formatter->format($hit);

        self::assertSame('inv-1', $result->title);
    }

    public function testFormatWithEmptyClientNameFallsBackToEmptyString(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');

        $result = $this->formatter->format(['id' => 'inv-1', 'invoiceId' => 'INV-001']);

        self::assertSame('', $result->subtitle);
    }

    public function testFormatWithMissingStatusResultsInNull(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');

        $result = $this->formatter->format(['id' => 'inv-1', 'invoiceId' => 'INV-001']);

        self::assertNull($result->status);
    }

    public function testFormatMetaIsNullWhenTotalMissing(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');

        $result = $this->formatter->format(['id' => 'inv-1', 'invoiceId' => 'INV-001']);

        self::assertNull($result->meta);
    }

    public function testFormatConvertsAmountFromMajorToMinorUnitsForMoneyFormatter(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');

        // total = 15.50 major units → 1550 minor units
        $this->moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(1550, new Currency('EUR')))
            ->willReturn('€15.50');

        $hit = ['id' => 'inv-1', 'total' => '15.50', 'client' => ['currencyCode' => 'EUR']];

        $result = $this->formatter->format($hit);

        self::assertSame('€15.50', $result->meta);
    }

    public function testFormatFallsBackToSystemCurrencyWhenCurrencyCodeMissing(): void
    {
        $this->router->method('generate')->willReturn('/invoices/inv-1');
        $this->systemConfig->method('getCurrency')->willReturn(new Currency('GBP'));

        $this->moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(1000, new Currency('GBP')))
            ->willReturn('£10.00');

        $hit = ['id' => 'inv-1', 'total' => '10.00'];

        $result = $this->formatter->format($hit);

        self::assertSame('£10.00', $result->meta);
    }

    public function testFormatGeneratesCorrectRoute(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', ['id' => 'my-inv-id'])
            ->willReturn('/invoices/my-inv-id');

        $this->formatter->format(['id' => 'my-inv-id']);
    }
}
