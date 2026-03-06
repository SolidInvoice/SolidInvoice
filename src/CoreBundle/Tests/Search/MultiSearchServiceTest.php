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

use Doctrine\Persistence\ManagerRegistry;
use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Exceptions\CommunicationException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Search\MultiSearchService;
use SolidInvoice\CoreBundle\Search\ParsedQuery;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchResult;
use Symfony\Component\Uid\Ulid;

final class MultiSearchServiceTest extends TestCase
{
    private Ulid $companyId;

    protected function setUp(): void
    {
        $this->companyId = new Ulid();
    }

    private function makeFormatter(string $indexName, SearchResult|null $result = null): ResultFormatterInterface
    {
        $formatter = $this->createMock(ResultFormatterInterface::class);
        $formatter->method('getIndexName')->willReturn($indexName);

        if ($result !== null) {
            $formatter->method('format')->willReturn($result);
        }

        return $formatter;
    }

    private function makeCompanySelector(bool $hasCompany = true): CompanySelector
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $selector = new CompanySelector($registry);

        if ($hasCompany) {
            $prop = new ReflectionProperty(CompanySelector::class, 'companyId');
            $prop->setValue($selector, $this->companyId);
        }

        return $selector;
    }

    /**
     * @param list<ResultFormatterInterface> $formatters
     */
    private function makeService(
        Client $client,
        CompanySelector $selector,
        array $formatters,
        string $prefix = 'test_',
    ): MultiSearchService {
        return new MultiSearchService($client, $selector, $formatters, $prefix);
    }

    private function makeResult(string $type = 'invoice'): SearchResult
    {
        return new SearchResult($type, 'id1', 'Title', 'Subtitle', '/url');
    }

    public function testReturnsEmptyArrayWhenNoCompanyIsSet(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('multiSearch');

        $service = $this->makeService($client, $this->makeCompanySelector(false), []);

        self::assertSame([], $service->search(new ParsedQuery('hello')));
    }

    public function testReturnsEmptyArrayForEmptyQueryWithNoFiltersSearchingAllIndices(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('multiSearch');

        $formatter = $this->makeFormatter('invoices');
        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);

        // Empty fulltext, no filters, no index restriction → skipped
        self::assertSame([], $service->search(new ParsedQuery('', [], [])));
    }

    public function testSearchesWhenFulltextIsProvided(): void
    {
        $result = $this->makeResult();
        $formatter = $this->makeFormatter('invoices', $result);

        $client = $this->createMock(Client::class);
        $client->expects(self::once())
            ->method('multiSearch')
            ->willReturn([
                'results' => [
                    ['indexUid' => 'test_invoices', 'hits' => [['id' => 'id1']]],
                ],
            ]);

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);
        $results = $service->search(new ParsedQuery('acme'));

        self::assertArrayHasKey('invoices', $results);
        self::assertCount(1, $results['invoices']);
        self::assertSame($result, $results['invoices'][0]);
    }

    public function testGroupsResultsByLogicalIndexName(): void
    {
        $invoiceResult = $this->makeResult('invoice');
        $quoteResult = $this->makeResult('quote');

        $client = $this->createMock(Client::class);
        $client->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'test_invoices', 'hits' => [['id' => 'i1']]],
                ['indexUid' => 'test_quotes', 'hits' => [['id' => 'q1']]],
            ],
        ]);

        $service = $this->makeService($client, $this->makeCompanySelector(), [
            $this->makeFormatter('invoices', $invoiceResult),
            $this->makeFormatter('quotes', $quoteResult),
        ]);

        $results = $service->search(new ParsedQuery('test'));

        self::assertArrayHasKey('invoices', $results);
        self::assertArrayHasKey('quotes', $results);
        self::assertCount(1, $results['invoices']);
        self::assertCount(1, $results['quotes']);
    }

    public function testAppliesCompanyFilterToEveryQuery(): void
    {
        $formatter = $this->makeFormatter('invoices', $this->makeResult());

        $capturedQueries = [];
        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willReturnCallback(static function (array $queries) use (&$capturedQueries) {
                $capturedQueries = $queries;
                return ['results' => []];
            });

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);
        $service->search(new ParsedQuery('test'));

        self::assertNotEmpty($capturedQueries);
        $filter = $capturedQueries[0]->toArray()['filter'];
        $expectedFilter = sprintf('companyId = "%s"', $this->companyId->toBase58());
        self::assertContains($expectedFilter, $filter);
    }

    public function testAppliesPerIndexFiltersAlongsideCompanyFilter(): void
    {
        $formatter = $this->makeFormatter('invoices', $this->makeResult());

        $capturedQueries = [];
        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willReturnCallback(static function (array $queries) use (&$capturedQueries) {
                $capturedQueries = $queries;
                return ['results' => []];
            });

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);
        $service->search(new ParsedQuery('test', [], ['invoices' => ['status = "paid"']]));

        self::assertNotEmpty($capturedQueries);
        $filter = $capturedQueries[0]->toArray()['filter'];
        self::assertContains('status = "paid"', $filter);
    }

    public function testEmptyHitsAreExcludedFromResults(): void
    {
        $formatter = $this->makeFormatter('invoices');

        $client = $this->createMock(Client::class);
        $client->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'test_invoices', 'hits' => []],
            ],
        ]);

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);

        self::assertSame([], $service->search(new ParsedQuery('test')));
    }

    public function testHitsFromUnknownIndexAreIgnored(): void
    {
        $formatter = $this->makeFormatter('invoices');

        $client = $this->createMock(Client::class);
        $client->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'test_unknown_index', 'hits' => [['id' => 'x']]],
            ],
        ]);

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);

        self::assertSame([], $service->search(new ParsedQuery('test')));
    }

    public function testReturnsEmptyArrayOnCommunicationException(): void
    {
        $formatter = $this->makeFormatter('invoices');

        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willThrowException($this->createMock(CommunicationException::class));

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);

        self::assertSame([], $service->search(new ParsedQuery('test')));
    }

    public function testReturnsEmptyArrayOnApiException(): void
    {
        $formatter = $this->makeFormatter('invoices');

        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willThrowException($this->createMock(ApiException::class));

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);

        self::assertSame([], $service->search(new ParsedQuery('test')));
    }

    public function testRestrictsToRequestedIndicesOnly(): void
    {
        $capturedQueries = [];
        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willReturnCallback(static function (array $queries) use (&$capturedQueries) {
                $capturedQueries = $queries;
                return ['results' => []];
            });

        $service = $this->makeService($client, $this->makeCompanySelector(), [
            $this->makeFormatter('invoices'),
            $this->makeFormatter('quotes'),
        ]);

        // Only query invoices, despite quotes formatter being registered
        $service->search(new ParsedQuery('test', ['invoices']));

        self::assertCount(1, $capturedQueries);
        self::assertStringContainsString('invoices', $capturedQueries[0]->toArray()['indexUid']);
    }

    public function testAppliesSortDirectives(): void
    {
        $capturedQueries = [];
        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willReturnCallback(static function (array $queries) use (&$capturedQueries) {
                $capturedQueries = $queries;
                return ['results' => []];
            });

        $service = $this->makeService($client, $this->makeCompanySelector(), [$this->makeFormatter('invoices')]);
        $service->search(new ParsedQuery('test', [], [], ['total:desc']));

        self::assertNotEmpty($capturedQueries);
        $queryArray = $capturedQueries[0]->toArray();
        self::assertArrayHasKey('sort', $queryArray);
        self::assertContains('total:desc', $queryArray['sort']);
    }

    public function testNoSortDirectiveWhenSortIsEmpty(): void
    {
        $capturedQueries = [];
        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willReturnCallback(static function (array $queries) use (&$capturedQueries) {
                $capturedQueries = $queries;
                return ['results' => []];
            });

        $service = $this->makeService($client, $this->makeCompanySelector(), [$this->makeFormatter('invoices')]);
        $service->search(new ParsedQuery('test'));

        self::assertNotEmpty($capturedQueries);
        self::assertArrayNotHasKey('sort', $capturedQueries[0]->toArray());
    }

    public function testIndexNamePrefixIsStrippedFromResults(): void
    {
        $result = $this->makeResult();
        $formatter = $this->makeFormatter('invoices', $result);

        $client = $this->createMock(Client::class);
        $client->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'myapp_invoices', 'hits' => [['id' => 'i1']]],
            ],
        ]);

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter], 'myapp_');
        $results = $service->search(new ParsedQuery('test'));

        self::assertArrayHasKey('invoices', $results);
    }

    public function testIndexPrefixIsAddedToQueryIndexUid(): void
    {
        $capturedQueries = [];
        $client = $this->createMock(Client::class);
        $client->method('multiSearch')
            ->willReturnCallback(static function (array $queries) use (&$capturedQueries) {
                $capturedQueries = $queries;
                return ['results' => []];
            });

        $service = $this->makeService($client, $this->makeCompanySelector(), [$this->makeFormatter('invoices')], 'myprefix_');
        $service->search(new ParsedQuery('test'));

        self::assertNotEmpty($capturedQueries);
        self::assertSame('myprefix_invoices', $capturedQueries[0]->toArray()['indexUid']);
    }

    public function testMultipleHitsPerIndexAreAllFormatted(): void
    {
        $r1 = new SearchResult('invoice', 'i1', 'INV-001', '', '/invoices/i1');
        $r2 = new SearchResult('invoice', 'i2', 'INV-002', '', '/invoices/i2');

        $formatter = $this->createMock(ResultFormatterInterface::class);
        $formatter->method('getIndexName')->willReturn('invoices');
        $formatter->method('format')->willReturnOnConsecutiveCalls($r1, $r2);

        $client = $this->createMock(Client::class);
        $client->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'test_invoices', 'hits' => [['id' => 'i1'], ['id' => 'i2']]],
            ],
        ]);

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);
        $results = $service->search(new ParsedQuery('inv'));

        self::assertCount(2, $results['invoices']);
        self::assertSame($r1, $results['invoices'][0]);
        self::assertSame($r2, $results['invoices'][1]);
    }

    public function testQueriesAreNotSentWhenRestrictedToNonexistentIndex(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects(self::never())->method('multiSearch');

        $service = $this->makeService($client, $this->makeCompanySelector(), [$this->makeFormatter('invoices')]);

        // 'contacts' is not a registered formatter index
        $results = $service->search(new ParsedQuery('test', ['contacts']));

        self::assertSame([], $results);
    }

    public function testQueryWithFiltersForSpecificIndexForcesQueryEvenWithEmptyFulltext(): void
    {
        $formatter = $this->makeFormatter('invoices', $this->makeResult());

        $client = $this->createMock(Client::class);
        $client->expects(self::once())->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'test_invoices', 'hits' => [['id' => 'i1']]],
            ],
        ]);

        $service = $this->makeService($client, $this->makeCompanySelector(), [$formatter]);
        // Empty fulltext but with per-index filters → query must still be sent
        $service->search(new ParsedQuery('', [], ['invoices' => ['status = "paid"']]));
    }
}
