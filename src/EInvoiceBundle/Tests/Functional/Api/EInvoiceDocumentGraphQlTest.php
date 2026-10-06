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

namespace SolidInvoice\EInvoiceBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Test\Factory\EInvoiceDocumentFactory;

/**
 * There is no existing functional GraphQL test anywhere else in this repository, so this is new
 * ground — see `design` §8 on SOL-94 ("Functional — GraphQL").
 */
#[Group('functional')]
final class EInvoiceDocumentGraphQlTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return EInvoiceDocument::class;
    }

    public function testItemQueryReturnsTheDocument(): void
    {
        $document = EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'invoice' => null,
            'sourceNumber' => 'INV-GQL-1',
        ]);

        $data = $this->requestGraphQl(
            'query($id: ID!) { eInvoiceDocument(id: $id) { id sourceNumber hasReceipt } }',
            ['id' => $this->getIriFromResource($document)],
        );

        self::assertSame('INV-GQL-1', $data['eInvoiceDocument']['sourceNumber']);
        self::assertFalse($data['eInvoiceDocument']['hasReceipt']);
    }

    public function testCollectionQueryReturnsOnlyTheActiveCompanysDocuments(): void
    {
        EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);
        $this->seedForeignDocument();

        $data = $this->requestGraphQl(
            'query { eInvoiceDocuments { edges { node { id } } } }',
        );

        self::assertCount(1, $data['eInvoiceDocuments']['edges']);
    }

    /**
     * Proves the schema shape, not a resolver's behaviour: `payload` is not a field on the
     * `EInvoiceDocument` type, so querying it is a validation error before anything resolves.
     */
    public function testPayloadIsNotAFieldOnTheType(): void
    {
        $document = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);

        $response = self::$client->request('POST', '/api/graphql', [
            'json' => ['query' => \sprintf('query { eInvoiceDocument(id: "%s") { id payload } }', $this->getIriFromResource($document))],
            'headers' => ['content-type' => 'application/json'],
        ]);

        $result = $response->toArray(false);

        self::assertArrayHasKey('errors', $result);
        self::assertStringContainsString('payload', (string) $result['errors'][0]['message']);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return array<string, mixed>
     */
    private function requestGraphQl(string $query, array $variables = []): array
    {
        $response = self::$client->request('POST', '/api/graphql', [
            'json' => ['query' => $query, 'variables' => $variables],
            'headers' => ['content-type' => 'application/json'],
        ]);

        $result = $response->toArray(false);
        self::assertArrayNotHasKey('errors', $result, $result['errors'][0]['message'] ?? '');

        return $result['data'];
    }

    private function seedForeignDocument(): EInvoiceDocument
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());

        $foreign = EInvoiceDocumentFactory::createOne(['company' => $otherCompany, 'invoice' => null]);

        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        return $foreign;
    }
}
