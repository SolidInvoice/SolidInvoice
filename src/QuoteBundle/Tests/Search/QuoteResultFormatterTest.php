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

namespace SolidInvoice\QuoteBundle\Tests\Search;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\QuoteBundle\Search\QuoteResultFormatter;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Routing\RouterInterface;

final class QuoteResultFormatterTest extends TestCase
{
    private MockObject&RouterInterface $router;

    private MockObject&MoneyFormatterInterface $moneyFormatter;

    private MockObject&SystemConfig $systemConfig;

    private QuoteResultFormatter $formatter;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->moneyFormatter = $this->createMock(MoneyFormatterInterface::class);
        $this->systemConfig = $this->createMock(SystemConfig::class);
        $this->formatter = new QuoteResultFormatter($this->router, $this->moneyFormatter, $this->systemConfig);
    }

    public function testImplementsResultFormatterInterface(): void
    {
        self::assertInstanceOf(ResultFormatterInterface::class, $this->formatter);
    }

    public function testImplementsQualifiedResultFormatterInterface(): void
    {
        self::assertInstanceOf(QualifiedResultFormatterInterface::class, $this->formatter);
    }

    public function testGetIndexNameReturnsQuotes(): void
    {
        self::assertSame('quotes', $this->formatter->getIndexName());
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
            ->with('_quotes_view', ['id' => 'q-1'])
            ->willReturn('/quotes/q-1');

        $this->moneyFormatter->method('format')->willReturn('$750.00');

        $hit = [
            'id' => 'q-1',
            'quoteId' => 'Q-001',
            'client' => ['name' => 'Beta Corp', 'currencyCode' => 'USD'],
            'status' => 'draft',
            'total' => '750.00',
        ];

        $result = $this->formatter->format($hit);

        self::assertSame('quote', $result->type);
        self::assertSame('q-1', $result->id);
        self::assertSame('Q-001', $result->title);
        self::assertSame('Beta Corp', $result->subtitle);
        self::assertSame('/quotes/q-1', $result->url);
        self::assertSame('draft', $result->status);
        self::assertSame('$750.00', $result->meta);
    }

    public function testFormatUsesIdAsTitleWhenQuoteIdMissing(): void
    {
        $this->router->method('generate')->willReturn('/quotes/q-1');

        $result = $this->formatter->format(['id' => 'q-1', 'client' => ['name' => 'Acme']]);

        self::assertSame('q-1', $result->title);
    }

    public function testFormatWithMissingClientNameFallsBackToEmptyString(): void
    {
        $this->router->method('generate')->willReturn('/quotes/q-1');

        $result = $this->formatter->format(['id' => 'q-1', 'quoteId' => 'Q-001']);

        self::assertSame('', $result->subtitle);
    }

    public function testFormatWithMissingStatusResultsInNull(): void
    {
        $this->router->method('generate')->willReturn('/quotes/q-1');

        $result = $this->formatter->format(['id' => 'q-1']);

        self::assertNull($result->status);
    }

    public function testFormatMetaIsNullWhenTotalMissing(): void
    {
        $this->router->method('generate')->willReturn('/quotes/q-1');

        $result = $this->formatter->format(['id' => 'q-1', 'quoteId' => 'Q-001']);

        self::assertNull($result->meta);
    }

    public function testFormatConvertsAmountFromMajorToMinorUnits(): void
    {
        $this->router->method('generate')->willReturn('/quotes/q-1');

        $this->moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(75000, new Currency('USD')))
            ->willReturn('$750.00');

        $hit = ['id' => 'q-1', 'total' => '750.00', 'client' => ['currencyCode' => 'USD']];

        $result = $this->formatter->format($hit);

        self::assertSame('$750.00', $result->meta);
    }

    public function testFormatFallsBackToSystemCurrencyWhenCurrencyCodeMissing(): void
    {
        $this->router->method('generate')->willReturn('/quotes/q-1');
        $this->systemConfig->method('getCurrency')->willReturn(new Currency('CHF'));

        $this->moneyFormatter
            ->expects(self::once())
            ->method('format')
            ->with(new Money(30000, new Currency('CHF')))
            ->willReturn('CHF 300.00');

        $hit = ['id' => 'q-1', 'total' => '300.00'];

        $result = $this->formatter->format($hit);

        self::assertSame('CHF 300.00', $result->meta);
    }

    public function testFormatGeneratesCorrectRoute(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('_quotes_view', ['id' => 'my-q-id'])
            ->willReturn('/quotes/my-q-id');

        $this->formatter->format(['id' => 'my-q-id']);
    }
}
