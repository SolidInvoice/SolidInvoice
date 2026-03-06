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

namespace SolidInvoice\CoreBundle\Tests\Action;

use Doctrine\Persistence\ManagerRegistry;
use Meilisearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Action\Search;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Search\MultiSearchService;
use SolidInvoice\CoreBundle\Search\ResultFormatterInterface;
use SolidInvoice\CoreBundle\Search\SearchQueryParser;
use SolidInvoice\CoreBundle\Search\SearchResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

final class SearchTest extends TestCase
{
    private MockObject&Client $client;

    private CompanySelector $companySelector;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $this->companySelector = new CompanySelector($registry);
    }

    private function setCompany(): Ulid
    {
        $companyId = new Ulid();
        $prop = new ReflectionProperty(CompanySelector::class, 'companyId');
        $prop->setValue($this->companySelector, $companyId);

        return $companyId;
    }

    /**
     * @param list<ResultFormatterInterface> $formatters
     */
    private function makeAction(array $formatters = []): Search
    {
        $service = new MultiSearchService($this->client, $this->companySelector, $formatters, 'test_');
        $parser = new SearchQueryParser($formatters);

        return new Search($service, $parser);
    }

    public function testReturnsEmptyJsonForQueryShorterThanTwoChars(): void
    {
        $this->client->expects(self::never())->method('multiSearch');

        $action = $this->makeAction();
        $response = $action(new Request(['q' => 'a']));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('[]', $response->getContent());
    }

    public function testReturnsEmptyJsonForEmptyQuery(): void
    {
        $this->client->expects(self::never())->method('multiSearch');

        $action = $this->makeAction();
        $response = $action(new Request(['q' => '']));

        self::assertSame('[]', $response->getContent());
    }

    public function testTrimsQueryBeforeLengthCheck(): void
    {
        $this->client->expects(self::never())->method('multiSearch');

        $action = $this->makeAction();
        // Single space after trimming becomes empty
        $response = $action(new Request(['q' => ' ']));

        self::assertSame('[]', $response->getContent());
    }

    public function testMissingQueryParameterTreatedAsEmpty(): void
    {
        $this->client->expects(self::never())->method('multiSearch');

        $action = $this->makeAction();
        $response = $action(new Request());

        self::assertSame('[]', $response->getContent());
    }

    public function testMinimumQueryLengthIsTwoChars(): void
    {
        $this->setCompany();

        $formatter = $this->createMock(ResultFormatterInterface::class);
        $formatter->method('getIndexName')->willReturn('invoices');

        $this->client->expects(self::once())
            ->method('multiSearch')
            ->willReturn(['results' => []]);

        $action = $this->makeAction([$formatter]);
        $response = $action(new Request(['q' => 'ab']));

        self::assertSame('[]', $response->getContent());
    }

    public function testSerializesSearchResultsToJson(): void
    {
        $this->setCompany();

        $expectedResult = new SearchResult(
            type: 'invoice',
            id: 'i1',
            title: 'INV-001',
            subtitle: 'Acme Corp',
            url: '/invoices/i1',
            status: 'paid',
            meta: '$1,500.00',
        );

        $formatter = $this->createMock(ResultFormatterInterface::class);
        $formatter->method('getIndexName')->willReturn('invoices');
        $formatter->method('format')->willReturn($expectedResult);

        $this->client->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'test_invoices', 'hits' => [['id' => 'i1']]],
            ],
        ]);

        $action = $this->makeAction([$formatter]);
        $response = $action(new Request(['q' => 'acme']));

        $data = json_decode((string) $response->getContent(), true);

        self::assertArrayHasKey('invoices', $data);
        self::assertCount(1, $data['invoices']);
        self::assertSame('invoice', $data['invoices'][0]['type']);
        self::assertSame('i1', $data['invoices'][0]['id']);
        self::assertSame('INV-001', $data['invoices'][0]['title']);
        self::assertSame('Acme Corp', $data['invoices'][0]['subtitle']);
        self::assertSame('/invoices/i1', $data['invoices'][0]['url']);
        self::assertSame('paid', $data['invoices'][0]['status']);
        self::assertSame('$1,500.00', $data['invoices'][0]['meta']);
    }

    public function testReturnsEmptyJsonWhenSearchServiceReturnsNoResults(): void
    {
        $this->setCompany();

        $formatter = $this->createMock(ResultFormatterInterface::class);
        $formatter->method('getIndexName')->willReturn('invoices');

        $this->client->method('multiSearch')->willReturn(['results' => []]);

        $action = $this->makeAction([$formatter]);
        $response = $action(new Request(['q' => 'acme']));

        self::assertSame('[]', $response->getContent());
    }

    public function testResponseContentTypeIsJson(): void
    {
        $action = $this->makeAction();
        $response = $action(new Request(['q' => 'a']));

        self::assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function testMultipleGroupsSerializedCorrectly(): void
    {
        $this->setCompany();

        $invoiceResult = new SearchResult('invoice', 'i1', 'INV-001', '', '/invoices/i1');
        $quoteResult = new SearchResult('quote', 'q1', 'Q-001', '', '/quotes/q1');

        $invoiceFormatter = $this->createMock(ResultFormatterInterface::class);
        $invoiceFormatter->method('getIndexName')->willReturn('invoices');
        $invoiceFormatter->method('format')->willReturn($invoiceResult);

        $quoteFormatter = $this->createMock(ResultFormatterInterface::class);
        $quoteFormatter->method('getIndexName')->willReturn('quotes');
        $quoteFormatter->method('format')->willReturn($quoteResult);

        $this->client->method('multiSearch')->willReturn([
            'results' => [
                ['indexUid' => 'test_invoices', 'hits' => [['id' => 'i1']]],
                ['indexUid' => 'test_quotes', 'hits' => [['id' => 'q1']]],
            ],
        ]);

        $action = $this->makeAction([$invoiceFormatter, $quoteFormatter]);
        $response = $action(new Request(['q' => 'test']));

        $data = json_decode((string) $response->getContent(), true);

        self::assertArrayHasKey('invoices', $data);
        self::assertArrayHasKey('quotes', $data);
        self::assertCount(1, $data['invoices']);
        self::assertCount(1, $data['quotes']);
    }

    public function testNoCompanyReturnsEmptyResult(): void
    {
        // No company set → MultiSearchService returns []
        $this->client->expects(self::never())->method('multiSearch');

        $action = $this->makeAction();
        $response = $action(new Request(['q' => 'acme']));

        self::assertSame('[]', $response->getContent());
    }
}
