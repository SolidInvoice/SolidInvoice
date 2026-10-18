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

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceAttachmentFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use function array_column;

#[Group('functional')]
final class InvoiceAttachmentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return InvoiceAttachment::class;
    }

    public function testGetCollection(): void
    {
        $invoice = InvoiceFactory::createOne(['company' => $this->company]);
        InvoiceAttachmentFactory::createOne(['invoice' => $invoice, 'company' => $this->company, 'filename' => 'first.pdf']);
        InvoiceAttachmentFactory::createOne(['invoice' => $invoice, 'company' => $this->company, 'filename' => 'second.pdf']);

        $data = $this->requestGetCollection('/api/invoices/' . $invoice->getId()->toString() . '/attachments');

        self::assertArraySubset([
            '@type' => 'Collection',
        ], $data);
        self::assertSame(2, $data['totalItems']);
        self::assertEqualsCanonicalizing(['first.pdf', 'second.pdf'], array_column($data['member'], 'filename'));

        foreach ($data['member'] as $member) {
            self::assertArrayNotHasKey('storageKey', $member);
        }
    }

    public function testGet(): void
    {
        $invoice = InvoiceFactory::createOne(['company' => $this->company]);
        $attachment = InvoiceAttachmentFactory::createOne([
            'invoice' => $invoice,
            'company' => $this->company,
            'filename' => 'receipt.pdf',
        ]);

        $data = $this->requestGet($this->getIriFromResource($attachment));

        self::assertSame('receipt.pdf', $data['filename']);
        self::assertArrayNotHasKey('storageKey', $data);
    }

    public function testPatchOnlyAcceptsSharedWithClient(): void
    {
        $invoice = InvoiceFactory::createOne(['company' => $this->company]);
        $attachment = InvoiceAttachmentFactory::createOne([
            'invoice' => $invoice,
            'company' => $this->company,
            'sharedWithClient' => false,
        ]);

        $data = $this->requestPatch($this->getIriFromResource($attachment), ['sharedWithClient' => true]);

        self::assertTrue($data['sharedWithClient']);
        self::assertArrayNotHasKey('storageKey', $data);
    }

    public function testDelete(): void
    {
        $invoice = InvoiceFactory::createOne(['company' => $this->company]);
        $attachment = InvoiceAttachmentFactory::createOne(['invoice' => $invoice, 'company' => $this->company]);

        $this->requestDelete($this->getIriFromResource($attachment));
    }

    public function testCannotAccessAttachmentFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignInvoice = InvoiceFactory::createOne(['company' => $otherCompany]);
        $foreignAttachment = InvoiceAttachmentFactory::createOne(['invoice' => $foreignInvoice, 'company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('GET', $this->getIriFromResource($foreignAttachment), [
            'headers' => ['accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * The sub-resource collection route does not re-check the parent invoice's own
     * visibility — it queries attachments by invoice id directly — so a foreign
     * invoice id does not 404 here the way a foreign attachment id does on `Get`.
     * What CompanyFilter has to guarantee instead is that none of the foreign
     * company's attachment rows leak through, even when they exist. This is the
     * functional check the design doc (SOL-79 §6) flagged as unverified.
     */
    public function testListingAttachmentsForAnInvoiceFromADifferentCompanyNeverLeaksForeignAttachments(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignInvoice = InvoiceFactory::createOne(['company' => $otherCompany]);
        InvoiceAttachmentFactory::createOne(['invoice' => $foreignInvoice, 'company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $data = $this->requestGetCollection('/api/invoices/' . $foreignInvoice->getId()->toString() . '/attachments');

        self::assertSame(0, $data['totalItems']);
    }
}
