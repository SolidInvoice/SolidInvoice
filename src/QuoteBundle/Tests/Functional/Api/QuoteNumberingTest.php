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

namespace SolidInvoice\QuoteBundle\Tests\Functional\Api;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\HttpFoundation\Response;
use function array_map;

#[Group('functional')]
final class QuoteNumberingTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Quote::class;
    }

    public function testABlankQuoteNumberIsGeneratedOnCreate(): void
    {
        QuoteFactory::createOne(['quoteId' => '11', 'status' => QuoteStatus::Draft]);

        $result = $this->requestPost('/api/quotes', $this->quotePayload());

        self::assertSame('12', $result['quoteId']);
    }

    public function testASuppliedQuoteNumberIsKept(): void
    {
        $result = $this->requestPost('/api/quotes', $this->quotePayload(['quoteId' => 'QUO-CUSTOM-3']));

        self::assertSame('QUO-CUSTOM-3', $result['quoteId']);
    }

    public function testADuplicateQuoteNumberIsRejected(): void
    {
        QuoteFactory::createOne(['quoteId' => 'QUO-1', 'status' => QuoteStatus::Draft, 'archived' => true]);

        $response = self::$client->request('POST', '/api/quotes', [
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
            'json' => $this->quotePayload(['quoteId' => 'QUO-1']),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame(
            [['propertyPath' => 'quoteId', 'message' => 'This quote number is already in use.']],
            array_map(
                static fn (array $violation): array => ['propertyPath' => $violation['propertyPath'], 'message' => $violation['message']],
                $response->toArray(false)['violations'],
            ),
        );
    }

    public function testCloneQuote(): void
    {
        $source = $this->requestPost('/api/quotes', $this->quotePayload(['quoteId' => '60', 'notes' => 'Cloned notes']));

        $clone = $this->requestPost(sprintf('/api/quotes/%s/clone', $source['id']), []);

        self::assertNotSame($source['id'], $clone['id']);
        self::assertSame('61', $clone['quoteId']);
        self::assertSame('draft', $clone['status']);
        self::assertStringStartsWith(CarbonImmutable::now('UTC')->format('Y-m-d'), $clone['created']);
        self::assertSame('Cloned notes', $clone['notes']);
        self::assertSame($source['client'], $clone['client']);
        self::assertSame($source['users'], $clone['users']);
        self::assertSame($source['total'], $clone['total']);
        self::assertSame($source['baseTotal'], $clone['baseTotal']);
        self::assertSame($this->lineValues($source['lines']), $this->lineValues($clone['lines']));
        self::assertNotSame($source['lines'][0]['id'], $clone['lines'][0]['id']);
    }

    public function testCloneOfAnotherCompanysQuoteIsNotFound(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignQuote = QuoteFactory::createOne(['client' => ClientFactory::createOne(['company' => $otherCompany]), 'company' => $otherCompany, 'status' => QuoteStatus::Draft]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('POST', sprintf('/api/quotes/%s/clone', $foreignQuote->getId()), [
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function quotePayload(array $overrides = []): array
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        return [
            'client' => $this->getIriFromResource($client),
            'users' => [$this->getIriFromResource($contact)],
            'lines' => [
                ['name' => 'Design', 'price' => 150, 'qty' => 2],
                ['name' => 'Hosting', 'price' => 20, 'qty' => 1],
            ],
            ...$overrides,
        ];
    }

    /**
     * @param list<array<string, mixed>> $lines
     *
     * @return list<list<mixed>>
     */
    private function lineValues(array $lines): array
    {
        return array_map(
            static fn (array $line): array => [$line['name'], $line['price'], $line['qty'], $line['total'], $line['position']],
            $lines,
        );
    }
}
