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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\InvoiceBundle\Search\RecurringInvoiceResultFormatter;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Routing\RouterInterface;

final class RecurringInvoiceResultFormatterTest extends TestCase
{
    private Stub&RouterInterface $router;

    private Stub&MoneyFormatterInterface $moneyFormatter;

    private Stub&SystemConfig $systemConfig;

    private RecurringInvoiceResultFormatter $formatter;

    protected function setUp(): void
    {
        $this->router = $this->createStub(RouterInterface::class);
        $this->moneyFormatter = $this->createStub(MoneyFormatterInterface::class);
        $this->systemConfig = $this->createStub(SystemConfig::class);
        $this->formatter = new RecurringInvoiceResultFormatter($this->router, $this->moneyFormatter, $this->systemConfig);
    }

    public function testImplementsResultFormatterInterface(): void
    {
        self::assertInstanceOf(ResultFormatterInterface::class, $this->formatter);
    }

    public function testImplementsQualifiedResultFormatterInterface(): void
    {
        self::assertInstanceOf(QualifiedResultFormatterInterface::class, $this->formatter);
    }

    public function testGetIndexNameReturnsRecurringInvoices(): void
    {
        self::assertSame('recurring_invoices', $this->formatter->getIndexName());
    }

    public function testGetSupportedQualifiersReturnsExpectedMapping(): void
    {
        self::assertSame([
            'status' => 'status',
            'amount' => 'total',
            'client' => 'client.name',
        ], $this->formatter->getSupportedQualifiers());
    }

    public function testCreatedQualifierIsNotSupported(): void
    {
        self::assertArrayNotHasKey('created', $this->formatter->getSupportedQualifiers());
    }

    public function testFormatMapsHitToSearchResult(): void
    {
        $this->router
            ->method('generate')
            ->with('_invoices_view_recurring', ['id' => 'rec-1'])
            ->willReturn('/invoices/recurring/rec-1');

        $this->moneyFormatter->method('format')->willReturn('$200.00');

        $hit = [
            'id' => 'rec-1',
            'client' => ['name' => 'Acme Corp', 'currencyCode' => 'USD'],
            'status' => 'active',
            'total' => '200.00',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('recurring_invoice', $result->type);
        self::assertSame('rec-1', $result->id);
        self::assertSame('Acme Corp', $result->title);
        self::assertSame('active', $result->subtitle);
        self::assertSame('/invoices/recurring/rec-1', $result->url);
        self::assertSame('active', $result->status);
        self::assertSame('$200.00', $result->meta);
    }

    public function testFormatUsesIdAsTitleWhenClientNameMissing(): void
    {
        $this->router->method('generate')->willReturn('/invoices/recurring/rec-1');

        $result = $this->formatter->format(['id' => 'rec-1', 'status' => 'active']);

        self::assertSame('rec-1', $result->title);
    }

    public function testFormatSubtitleIsStatusWhenPresent(): void
    {
        $this->router->method('generate')->willReturn('/invoices/recurring/rec-1');

        $result = $this->formatter->format(['id' => 'rec-1', 'status' => 'paused']);

        self::assertSame('paused', $result->subtitle);
    }

    public function testFormatSubtitleIsEmptyStringWhenStatusMissing(): void
    {
        $this->router->method('generate')->willReturn('/invoices/recurring/rec-1');

        $result = $this->formatter->format(['id' => 'rec-1']);

        self::assertSame('', $result->subtitle);
    }

    public function testFormatMetaIsNullWhenTotalMissing(): void
    {
        $this->router->method('generate')->willReturn('/invoices/recurring/rec-1');

        $result = $this->formatter->format(['id' => 'rec-1', 'status' => 'active']);

        self::assertNull($result->meta);
    }

    public function testFormatConvertsAmountFromMajorToMinorUnits(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/recurring/rec-1');

        $moneyFormatter = $this->createMock(MoneyFormatterInterface::class);
        $moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(50000, new Currency('EUR')))
            ->willReturn('€500.00');

        $formatter = new RecurringInvoiceResultFormatter($router, $moneyFormatter, $this->systemConfig);
        $hit = ['id' => 'rec-1', 'total' => '500.00', 'client' => ['currencyCode' => 'EUR']];

        $result = $formatter->format($hit);

        self::assertSame('€500.00', $result->meta);
    }

    public function testFormatFallsBackToSystemCurrencyWhenCurrencyCodeMissing(): void
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/recurring/rec-1');

        $systemConfig = $this->createStub(SystemConfig::class);
        $systemConfig->method('getCurrency')->willReturn(new Currency('GBP'));

        $moneyFormatter = $this->createMock(MoneyFormatterInterface::class);
        $moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(2500, new Currency('GBP')))
            ->willReturn('£25.00');

        $formatter = new RecurringInvoiceResultFormatter($router, $moneyFormatter, $systemConfig);
        $hit = ['id' => 'rec-1', 'total' => '25.00'];

        $result = $formatter->format($hit);

        self::assertSame('£25.00', $result->meta);
    }

    public function testFormatGeneratesCorrectRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('_invoices_view_recurring', ['id' => 'my-rec-id'])
            ->willReturn('/invoices/recurring/my-rec-id');

        $formatter = new RecurringInvoiceResultFormatter($router, $this->moneyFormatter, $this->systemConfig);
        $formatter->format(['id' => 'my-rec-id']);
    }
}
