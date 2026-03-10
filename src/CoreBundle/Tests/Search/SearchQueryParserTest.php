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

namespace SolidInvoice\CoreBundle\Tests\Search;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Search\QualifiedResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchQueryParser;
use SolidInvoice\CoreBundle\Search\SearchResult;

final class SearchQueryParserTest extends TestCase
{
    private SearchQueryParser $parser;

    protected function setUp(): void
    {
        // Invoice formatter supports status, amount, client, created, sort
        $invoiceFormatter = new class() implements QualifiedResultFormatterInterface {
            public function getIndexName(): string
            {
                return 'invoices';
            }

            public function format(array $hit): SearchResult
            {
                return new SearchResult('i', 'i', 't', 's', 'u');
            }

            public function getSupportedQualifiers(): array
            {
                return ['status' => 'status', 'amount' => 'total', 'client' => 'client.name', 'created' => 'created'];
            }
        };

        // Payment formatter: status + client only (no amount)
        $paymentFormatter = new class() implements QualifiedResultFormatterInterface {
            public function getIndexName(): string
            {
                return 'payments';
            }

            public function format(array $hit): SearchResult
            {
                return new SearchResult('p', 'i', 't', 's', 'u');
            }

            public function getSupportedQualifiers(): array
            {
                return ['status' => 'status', 'client' => 'client.name'];
            }
        };

        // Client formatter: no qualified search
        $clientFormatter = new class() implements ResultFormatterInterface {
            public function getIndexName(): string
            {
                return 'clients';
            }

            public function format(array $hit): SearchResult
            {
                return new SearchResult('c', 'i', 't', 's', 'u');
            }
        };

        $this->parser = new SearchQueryParser([$invoiceFormatter, $paymentFormatter, $clientFormatter]);
    }

    public function testPlainTextQueryPassedThrough(): void
    {
        $result = $this->parser->parse('acme corp');

        self::assertSame('acme corp', $result->fulltext);
        self::assertSame([], $result->indices);
        self::assertSame([], $result->indexFilters['invoices'] ?? []);
        self::assertSame([], $result->sort);
    }

    public function testInQualifierRestrictsIndices(): void
    {
        $result = $this->parser->parse('in:invoices acme');

        self::assertSame(['invoices'], $result->indices);
        self::assertSame('acme', $result->fulltext);
    }

    public function testInQualifierMultipleIndices(): void
    {
        $result = $this->parser->parse('in:invoices,payments acme');

        self::assertSame(['invoices', 'payments'], $result->indices);
    }

    public function testInQualifierIgnoresUnknownIndex(): void
    {
        $result = $this->parser->parse('in:invoices,unknown acme');

        self::assertSame(['invoices'], $result->indices);
    }

    public function testStatusEqualityFilter(): void
    {
        $result = $this->parser->parse('status:paid');

        self::assertSame(['status = "paid"'], $result->indexFilters['invoices'] ?? []);
        self::assertSame(['status = "paid"'], $result->indexFilters['payments'] ?? []);
        self::assertSame([], $result->indexFilters['clients'] ?? []);
        self::assertSame('', $result->fulltext);
    }

    public function testStatusMultiValueFilter(): void
    {
        $result = $this->parser->parse('status:paid,pending');

        self::assertSame(['status IN ["paid", "pending"]'], $result->indexFilters['invoices'] ?? []);
        self::assertSame(['status IN ["paid", "pending"]'], $result->indexFilters['payments'] ?? []);
    }

    public function testAmountGreaterThanFilter(): void
    {
        $result = $this->parser->parse('amount:>100');

        self::assertSame(['total > 100'], $result->indexFilters['invoices'] ?? []);
        // payments does NOT support amount
        self::assertSame([], $result->indexFilters['payments'] ?? []);
    }

    public function testAmountGreaterThanOrEqualFilter(): void
    {
        $result = $this->parser->parse('amount:>=100');

        self::assertSame(['total >= 100'], $result->indexFilters['invoices'] ?? []);
    }

    public function testAmountLessThanFilter(): void
    {
        $result = $this->parser->parse('amount:<500');

        self::assertSame(['total < 500'], $result->indexFilters['invoices'] ?? []);
    }

    public function testAmountLessThanOrEqualFilter(): void
    {
        $result = $this->parser->parse('amount:<=500');

        self::assertSame(['total <= 500'], $result->indexFilters['invoices'] ?? []);
    }

    public function testAmountExactFilter(): void
    {
        $result = $this->parser->parse('amount:100');

        self::assertSame(['total = 100'], $result->indexFilters['invoices'] ?? []);
    }

    public function testAmountRangeFilter(): void
    {
        $result = $this->parser->parse('amount:100..500');

        self::assertSame(['total >= 100 AND total <= 500'], $result->indexFilters['invoices'] ?? []);
    }

    public function testClientNameFilter(): void
    {
        $result = $this->parser->parse('client:acme');

        self::assertSame(['client.name = "acme"'], $result->indexFilters['invoices'] ?? []);
    }

    public function testClientNameWithQuotesFilter(): void
    {
        $result = $this->parser->parse('client:"Acme Corp"');

        self::assertSame(['client.name = "Acme Corp"'], $result->indexFilters['invoices'] ?? []);
    }

    public function testCreatedAfterFilter(): void
    {
        $result = $this->parser->parse('created:>2024-01-01');

        self::assertSame(['created > 1704067200'], $result->indexFilters['invoices'] ?? []);
    }

    public function testCreatedRangeFilter(): void
    {
        $result = $this->parser->parse('created:2024-01-01..2024-12-31');

        self::assertSame(['created >= 1704067200 AND created <= 1735689599'], $result->indexFilters['invoices'] ?? []);
    }

    public function testSortAscending(): void
    {
        $result = $this->parser->parse('sort:amount invoices');

        self::assertSame(['total:asc'], $result->sort);
        self::assertSame('invoices', $result->fulltext);
    }

    public function testSortDescending(): void
    {
        $result = $this->parser->parse('sort:amount_desc');

        self::assertSame(['total:desc'], $result->sort);
    }

    public function testSortDateAscending(): void
    {
        $result = $this->parser->parse('sort:date');

        self::assertSame(['created:asc'], $result->sort);
    }

    public function testSortDateDescending(): void
    {
        $result = $this->parser->parse('sort:date_desc');

        self::assertSame(['created:desc'], $result->sort);
    }

    public function testAmountQualifierOnlyAppliesToSupportingIndices(): void
    {
        // payment formatter does NOT support 'amount', so amount filter should not appear
        // when scoped only to payments
        $result = $this->parser->parse('in:payments amount:>100');

        self::assertSame(['payments'], $result->indices);
        // 'amount' is not in payments' supported qualifiers -> filter silently dropped
        self::assertSame([], $result->indexFilters['payments'] ?? []);
    }

    public function testCombinedQuery(): void
    {
        $result = $this->parser->parse('in:invoices status:paid amount:>100 acme corp');

        self::assertSame(['invoices'], $result->indices);
        self::assertSame(['status = "paid"', 'total > 100'], $result->indexFilters['invoices'] ?? []);
        self::assertSame('acme corp', $result->fulltext);
    }

    public function testQualifierStrippedFromFulltext(): void
    {
        $result = $this->parser->parse('status:paid hello world');

        self::assertSame('hello world', $result->fulltext);
        // Verify filter is still applied to invoices
        self::assertSame(['status = "paid"'], $result->indexFilters['invoices'] ?? []);
    }

    public function testUnknownQualifierKeptInFulltext(): void
    {
        // 'foo:bar' is not a known qualifier; treat as plain text
        $result = $this->parser->parse('foo:bar hello');

        self::assertSame('foo:bar hello', $result->fulltext);
    }

    public function testInvalidDateReturnsNoFilter(): void
    {
        $result = $this->parser->parse('created:>notadate');

        self::assertSame([], $result->indexFilters['invoices'] ?? []);
    }

    public function testNonNumericAmountOperandReturnsNoFilter(): void
    {
        $result = $this->parser->parse('amount:>notanumber');

        self::assertSame([], $result->indexFilters['invoices'] ?? []);
    }

    public function testEmptyRangeBoundaryReturnsNoFilter(): void
    {
        $result = $this->parser->parse('created:2024-01-01..');

        // Empty $to boundary — should not crash and should return empty filter
        self::assertSame([], $result->indexFilters['invoices'] ?? []);
    }

    #[DataProvider('whitespaceProvider')]
    public function testLeadingTrailingWhitespaceInFulltext(string $input, string $expected): void
    {
        $result = $this->parser->parse($input);

        self::assertSame($expected, $result->fulltext);
    }

    /**
     * @return list<array{string, string}>
     */
    public static function whitespaceProvider(): array
    {
        return [
            ['  acme  ', 'acme'],
            ['status:paid  acme  ', 'acme'],
            ['  status:paid', ''],
        ];
    }
}
