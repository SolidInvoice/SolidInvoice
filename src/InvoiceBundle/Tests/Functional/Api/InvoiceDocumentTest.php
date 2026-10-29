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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use function str_starts_with;

#[Group('functional')]
final class InvoiceDocumentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testPdf(): void
    {
        $invoice = $this->createInvoice(['invoiceId' => 'INV/2026 "7"']);

        $response = self::$client->request('GET', $this->getIriFromResource($invoice) . '/pdf', ['headers' => ['accept' => 'application/pdf']]);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/pdf');
        self::assertResponseHeaderSame('content-disposition', 'inline; filename=invoice_INV_2026__7_.pdf');
        self::assertStringStartsWith('%PDF-', $response->getContent());

        $etag = $response->getHeaders()['etag'][0] ?? '';
        self::assertNotSame('', $etag);

        self::$client->request('GET', $this->getIriFromResource($invoice) . '/pdf', ['headers' => ['accept' => 'application/pdf', 'if-none-match' => $etag]]);

        self::assertResponseStatusCodeSame(304);
    }

    public function testPdfOfAnotherCompanyIsNotFound(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignInvoice = InvoiceFactory::createOne(['client' => ClientFactory::createOne(['company' => $otherCompany])]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('GET', $this->getIriFromResource($foreignInvoice) . '/pdf', ['headers' => ['accept' => 'application/pdf']]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testPdfRequiresAuthentication(): void
    {
        $invoice = $this->createInvoice();

        self::createClient()->request('GET', $this->getIriFromResource($invoice) . '/pdf', ['headers' => ['accept' => 'application/pdf']]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLinksWithoutOnlinePaymentMethod(): void
    {
        $invoice = $this->createInvoice();
        $uuid = $invoice->getUuid()->toString();

        $data = $this->requestGet($this->getIriFromResource($invoice));

        self::assertSame('http://localhost/view/invoice/' . $uuid, $data['publicUrl']);
        self::assertSame('http://localhost/view/invoice/' . $uuid . '.pdf', $data['pdfUrl']);
        self::assertNull($data['payUrl']);

        $member = $this->requestGetCollection('/api/invoices')['member'][0];

        self::assertTrue(str_starts_with($member['publicUrl'], 'http://localhost/view/invoice/'));
        self::assertTrue(str_starts_with($member['pdfUrl'], 'http://localhost/view/invoice/'));
        self::assertNull($member['payUrl']);
    }

    public function testPayUrlWithOnlinePaymentMethod(): void
    {
        PaymentMethodFactory::createOne(['internal' => false, 'enabled' => true, 'company' => $this->company]);
        $invoice = $this->createInvoice();

        $data = $this->requestGet($this->getIriFromResource($invoice));

        self::assertSame('http://localhost/payments/create/' . $invoice->getUuid()->toString(), $data['payUrl']);
        self::assertSame($data['payUrl'], $this->requestGetCollection('/api/invoices')['member'][0]['payUrl']);
    }

    public function testPayUrlIsNullForPaidInvoice(): void
    {
        PaymentMethodFactory::createOne(['internal' => false, 'enabled' => true, 'company' => $this->company]);
        $invoice = $this->createInvoice(['status' => InvoiceStatus::Paid]);

        self::assertNull($this->requestGet($this->getIriFromResource($invoice))['payUrl']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createInvoice(array $attributes = []): Invoice
    {
        $client = ClientFactory::createOne();

        return InvoiceFactory::createOne([
            'client' => $client,
            'users' => ContactFactory::createMany(1, ['client' => $client]),
            'lines' => [new Line()->setName('Test Item')->setQty(1)->setPrice(10000)],
            ...$attributes,
        ]);
    }
}
