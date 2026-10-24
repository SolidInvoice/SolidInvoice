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

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceTransition;
use SolidInvoice\EInvoiceBundle\Test\Factory\EInvoiceDocumentFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * `design` §8 on SOL-94 ("Functional — REST").
 */
#[Group('functional')]
final class EInvoiceDocumentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return EInvoiceDocument::class;
    }

    public function testGetCollectionReturnsOnlyTheCompanysDocuments(): void
    {
        EInvoiceDocumentFactory::createMany(2, ['company' => $this->company, 'invoice' => null]);
        $this->seedForeignDocument();

        $data = $this->requestGetCollection('/api/e-invoice-documents');
        $items = $data['member'] ?? $data['hydra:member'] ?? [];

        self::assertCount(2, $items);
    }

    public function testGetItemExposesExactlyTheReadGroupFields(): void
    {
        $document = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);

        $data = $this->requestGet($this->getIriFromResource($document));

        foreach ([
            'id', 'status', 'sourceNumber', 'channel', 'profile', 'payloadFormat',
            'payloadMediaType', 'payloadFilename', 'payloadHash', 'externalId', 'errorCode',
            'errorMessage', 'transmittedAt', 'completedAt', 'created', 'updated', 'invoice',
            'creditNote', 'hasReceipt',
        ] as $field) {
            self::assertArrayHasKey($field, $data, "Expected '{$field}' in the read group.");
        }

        foreach (['payload', 'receipt', 'company'] as $field) {
            self::assertArrayNotHasKey($field, $data, "'{$field}' must never be in the read group.");
        }

        self::assertFalse($data['hasReceipt']);
    }

    public function testGetNestedCollectionForInvoice(): void
    {
        $invoice = InvoiceFactory::createOne(['company' => $this->company]);
        $document = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => $invoice]);
        // A document on a different invoice must not leak into this invoice's nested collection.
        EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => InvoiceFactory::createOne(['company' => $this->company])]);

        $data = $this->requestGetCollection($this->getIriFromResource($invoice) . '/e-invoice-documents');
        $items = $data['member'] ?? $data['hydra:member'] ?? [];

        self::assertCount(1, $items);
        self::assertSame($this->getIriFromResource($document), $items[0]['@id']);
    }

    public function testFilterByStatus(): void
    {
        EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);
        $accepted = $this->createAcceptedDocument();

        $data = $this->requestGetCollection('/api/e-invoice-documents?status=accepted');
        $items = $data['member'] ?? $data['hydra:member'] ?? [];

        self::assertCount(1, $items);
        self::assertSame($this->getIriFromResource($accepted), $items[0]['@id']);
    }

    public function testFilterByChannel(): void
    {
        EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null, 'channel' => 'peppol']);
        $kseif = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null, 'channel' => 'ksef']);

        $data = $this->requestGetCollection('/api/e-invoice-documents?channel=ksef');
        $items = $data['member'] ?? $data['hydra:member'] ?? [];

        self::assertCount(1, $items);
        self::assertSame($this->getIriFromResource($kseif), $items[0]['@id']);
    }

    public function testOrderByCreatedDescending(): void
    {
        // Explicit, one-day-apart timestamps: two documents created back-to-back in the same test
        // can land on the same second, which would make a plain creation-order assertion flaky.
        $first = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);
        $first->setCreated(CarbonImmutable::now()->subDays(2));

        $second = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);
        $second->setCreated(CarbonImmutable::now()->subDay());
        self::getContainer()->get('doctrine')->getManager()->flush();

        $data = $this->requestGetCollection('/api/e-invoice-documents?order[created]=desc');
        $items = $data['member'] ?? $data['hydra:member'] ?? [];

        self::assertSame($this->getIriFromResource($second), $items[0]['@id']);
        self::assertSame($this->getIriFromResource($first), $items[1]['@id']);
    }

    public function testDownloadPayload(): void
    {
        $document = EInvoiceDocumentFactory::createOne([
            'company' => $this->company,
            'invoice' => null,
            'payload' => '<Invoice>content</Invoice>',
            'payloadMediaType' => 'application/xml',
            'payloadFilename' => 'invoice-ubl.xml',
        ]);

        $response = self::$client->request('GET', $this->getIriFromResource($document) . '/payload');

        self::assertResponseStatusCodeSame(200);
        self::assertSame('<Invoice>content</Invoice>', $response->getContent());
        self::assertResponseHeaderSame('Content-Type', 'application/xml');
        self::assertStringContainsString('attachment', $response->getHeaders()['content-disposition'][0]);
        self::assertStringContainsString('invoice-ubl.xml', $response->getHeaders()['content-disposition'][0]);
        self::assertResponseHeaderSame('ETag', '"' . $document->getPayloadHash() . '"');
    }

    public function testDownloadReceiptIsNotFoundOnAPendingDocument(): void
    {
        $document = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);

        self::$client->request('GET', $this->getIriFromResource($document) . '/receipt');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDownloadReceiptOnAnAcceptedDocument(): void
    {
        $document = $this->createAcceptedDocument();

        $response = self::$client->request('GET', $this->getIriFromResource($document) . '/receipt');

        self::assertResponseStatusCodeSame(200);
        self::assertSame('<Receipt>UPO-123</Receipt>', $response->getContent());
    }

    public function testCollectionWriteVerbsAreNotAllowed(): void
    {
        self::$client->request('POST', '/api/e-invoice-documents', ['json' => []]);
        self::assertResponseStatusCodeSame(405);
    }

    public function testItemWriteVerbsAreNotAllowed(): void
    {
        $document = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);
        $iri = $this->getIriFromResource($document);

        self::$client->request('PATCH', $iri, ['json' => [], 'headers' => ['content-type' => 'application/merge-patch+json']]);
        self::assertResponseStatusCodeSame(405);

        self::$client->request('DELETE', $iri);
        self::assertResponseStatusCodeSame(405);
    }

    public function testCannotAccessAnotherCompanysDocument(): void
    {
        $foreign = $this->seedForeignDocument();
        $iri = $this->getIriFromResource($foreign);

        self::$client->request('GET', $iri);
        self::assertResponseStatusCodeSame(404);

        self::$client->request('GET', $iri . '/payload');
        self::assertResponseStatusCodeSame(404);

        self::$client->request('GET', $iri . '/receipt');
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Reaches `accepted` through the workflow, never by writing `status` in a factory state —
     * {@see EInvoiceDocumentFactory::defaults()} says why. `externalId` and `receipt` are written
     * while the marking is still `transmitted` (non-terminal), matching the one window the design
     * and {@see \SolidInvoice\EInvoiceBundle\Listener\ImmutablePayloadListener} both carve out for
     * them — see `design` §6 on SOL-89.
     */
    private function createAcceptedDocument(): EInvoiceDocument
    {
        $document = EInvoiceDocumentFactory::createOne(['company' => $this->company, 'invoice' => null]);

        $workflow = self::getContainer()->get('state_machine.einvoice_document');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $em = self::getContainer()->get('doctrine')->getManager();

        $workflow->apply($document, EInvoiceTransition::Queue->value);
        $workflow->apply($document, EInvoiceTransition::Transmit->value);

        $em->flush();

        // The marking is still `transmitted` (non-terminal) here, so the guarded setters allow
        // this write. Flushing before the `accept` transition keeps it that way: applying the
        // transition in-memory already flips $document->status to the terminal `accepted` place,
        // and a single flush covering both changes would make ImmutablePayloadListener see that
        // terminal status and reject the very write the design says must remain allowed.
        $document->setExternalId('REMOTE-123');
        $document->setReceipt('<Receipt>UPO-123</Receipt>');

        $em->flush();

        $workflow->apply($document, EInvoiceTransition::Accept->value);
        $em->flush();

        return $document;
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
