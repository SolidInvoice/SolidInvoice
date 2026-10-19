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

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Api;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\CreditNoteLine;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\CreditNoteFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;

/**
 * CN-6 of 6 for SOL-69. Mirrors {@see InvoiceTest} and {@see InvoiceLineTest} for the
 * create-from-invoice sub-resource. See `design` §10 on SOL-69.
 */
#[Group('functional')]
final class CreditNoteTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return CreditNote::class;
    }

    public function testGetCollection(): void
    {
        $client = ClientFactory::createOne();
        CreditNoteFactory::createSequence(static fn (): iterable => [
            ['client' => $client, 'status' => CreditNoteStatus::Draft, 'creditNoteId' => 'CN-0001'],
            ['client' => $client, 'status' => CreditNoteStatus::Draft, 'creditNoteId' => 'CN-0002'],
            ['client' => $client, 'status' => CreditNoteStatus::Draft, 'creditNoteId' => 'CN-0003'],
        ]);

        $data = $this->requestGetCollection('/api/credit-notes');

        self::assertArraySubset([
            '@context' => $this->getContextForResource(CreditNote::class),
            '@id' => '/api/credit-notes',
            '@type' => 'Collection',
        ], $data);
    }

    public function testCannotAccessCreditNoteFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignCreditNote = CreditNoteFactory::createOne(['client' => $foreignClient]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('GET', $this->getIriFromResource($foreignCreditNote), [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreate(): void
    {
        $client = ClientFactory::createOne();

        $data = [
            'client' => $this->getIriFromResource($client),
            'reason' => 'Returned goods',
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'lines' => [
                [
                    'price' => 100,
                    'qty' => 1,
                    'name' => 'Foo Item',
                ],
            ],
        ];

        $result = $this->requestPost('/api/credit-notes', $data);

        self::assertTrue(Ulid::isValid($result['id'], Ulid::FORMAT_BASE_32));
        self::assertTrue(Ulid::isValid($result['lines'][0]['id'], Ulid::FORMAT_BASE_32));

        self::assertJsonContains([
            '@context' => $this->getContextForResource($this->getResourceClass()),
            '@type' => 'CreditNote',
            'client' => $this->getIriFromResource($client),
            'invoice' => null,
            'reason' => 'Returned goods',
            'creditNoteDate' => Carbon::now()->format('Y-m-d\T00:00:00+02:00'),
            'lines' => [
                [
                    'name' => 'Foo Item',
                    'price' => 100,
                    'qty' => 1,
                    'total' => 100,
                    'description' => null,
                    'taxes' => [],
                    'position' => 0,
                ],
            ],
            'status' => 'draft',
            'invoiceTypeCode' => '381',
            'total' => 90,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
        ]);
    }

    public function testGet(): void
    {
        $client = ClientFactory::createOne();

        /** @var CreditNote $creditNote */
        $creditNote = CreditNoteFactory::createOne([
            'client' => $client,
            'status' => CreditNoteStatus::Draft,
            'reason' => 'Returned goods',
            // The DATE_IMMUTABLE column truncates the time when persisting. Without
            // Foundry's proxy auto-refresh (unavailable on Symfony 8), the in-memory
            // entity keeps whatever time it was created with, so use an explicit
            // midnight value to stay consistent with the database round-trip.
            'creditNoteDate' => CarbonImmutable::today(),
            'discount' => new Discount()
                ->setType(Discount::TYPE_PERCENTAGE)
                ->setValue(0),
            'lines' => [
                new CreditNoteLine()
                    ->setName('Test Item')
                    ->setQty(1)
                    ->setPrice(10000),
            ],
            'total' => 100,
            'baseTotal' => 100,
            'tax' => 0,
        ]);

        $data = $this->requestGet($this->getIriFromResource($creditNote));

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/CreditNote',
            '@id' => $this->getIriFromResource($creditNote),
            '@type' => 'CreditNote',
            'id' => $creditNote->getId()
                ->toString(),
            'creditNoteId' => '',
            'uuid' => $creditNote->getUuid()
                ->toString(),
            'client' => '/api/clients/' . $creditNote->getClient()->getId(),
            'invoice' => null,
            'reason' => 'Returned goods',
            'creditNoteDate' => $creditNote->getCreditNoteDate()->format('Y-m-d\T00:00:00+02:00'),
            'lines' => [
                [
                    '@id' => $this->getIriFromResource($creditNote->getLines()->first()),
                    '@type' => 'CreditNoteLine',
                    'id' => $creditNote->getLines()
                        ->first()
                        ->getId()
                        ->toString(),
                    'name' => 'Test Item',
                    'price' => 100,
                    'qty' => 1,
                    'total' => 100,
                    'description' => null,
                    'taxes' => [],
                    'position' => 0,
                ],
            ],
            'invoiceTaxes' => [],
            'status' => 'draft',
            'invoiceTypeCode' => '381',
            'total' => 100,
            'baseTotal' => 100,
            'tax' => 0,
            // InvoiceSaveListener nulls a zero-value discount's type on persist, mirroring
            // the same rule for Invoice - see InvoiceSaveListener::checkDiscount().
            'discount' => [
                'type' => null,
                'value' => 0,
            ],
            'terms' => $creditNote->getTerms(),
            'notes' => $creditNote->getNotes(),
            'paymentTerms' => 'custom',
            'withholdingAmount' => 0,
            'payableAmount' => 100,
        ], $data);
    }

    public function testEdit(): void
    {
        /** @var CreditNote $creditNote */
        $creditNote = CreditNoteFactory::createOne([
            'client' => ClientFactory::createOne(),
            'status' => CreditNoteStatus::Draft,
            'reason' => 'Returned goods',
            'lines' => [
                new CreditNoteLine()
                    ->setName('Test Item')
                    ->setQty(1)
                    ->setPrice(10000),
            ],
        ]);

        $data = $this->requestPatch(
            $this->getIriFromResource($creditNote),
            ['reason' => 'Updated reason'],
        );

        self::assertSame('Updated reason', $data['reason']);
    }

    public function testDelete(): void
    {
        $creditNote = CreditNoteFactory::createOne(['client' => ClientFactory::createOne()]);

        $this->requestDelete($this->getIriFromResource($creditNote));
    }

    public function testCreateFromInvoice(): void
    {
        $client = ClientFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            // Large enough that the 50 credited below stays within CreditNoteWithinInvoiceTotal's cap.
            'lines' => [new Line()->setName('Original Item')->setQty(1)->setPrice(10000)],
        ]);

        $result = $this->requestPost('/api/invoices/' . $invoice->getId() . '/credit-notes', [
            'reason' => 'Service not delivered',
            'lines' => [
                [
                    'price' => 50,
                    'qty' => 1,
                    'name' => 'Refunded Item',
                ],
            ],
        ]);

        self::assertSame($this->getIriFromResource($invoice), $result['invoice']);
        self::assertSame($this->getIriFromResource($client), $result['client']);
        self::assertSame('Service not delivered', $result['reason']);
    }

    /**
     * The processor resolves the invoice through `InvoiceRepository::find()`, which is
     * subject to `CompanyFilter` like every other query path. An invoice belonging to
     * another company is invisible to it, so the create-from-invoice route 404s instead
     * of letting a credit note attach to a tenant it cannot see.
     */
    public function testCreateFromInvoiceOnForeignCompanyInvoiceIs404(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignInvoice = InvoiceFactory::createOne(['client' => $foreignClient]);

        // Doctrine's identity map would otherwise hand the processor's find() this exact
        // in-memory object straight back, bypassing CompanyFilter entirely because no SQL
        // query actually runs. A real attacker's request never has it preloaded like this -
        // clearing here reproduces that, matching the pattern in EInvoiceDocumentIsolationTest.
        self::getContainer()->get('doctrine')->getManager()->clear();

        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request(
            method: Request::METHOD_POST,
            url: '/api/invoices/' . $foreignInvoice->getId() . '/credit-notes',
            options: [
                'json' => ['reason' => 'Should not be allowed'],
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
