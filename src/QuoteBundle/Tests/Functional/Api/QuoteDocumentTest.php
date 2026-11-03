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

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use function str_starts_with;

#[Group('functional')]
final class QuoteDocumentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Quote::class;
    }

    public function testPdf(): void
    {
        $quote = $this->createQuote(['quoteId' => 'QUO/2026 "7"']);

        $response = self::$client->request('GET', $this->getIriFromResource($quote) . '/pdf', ['headers' => ['accept' => '*/*']]);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/pdf');
        self::assertResponseHeaderSame('content-disposition', 'inline; filename=quote_QUO_2026__7_.pdf');
        self::assertStringStartsWith('%PDF-', $response->getContent());

        $etag = $response->getHeaders()['etag'][0] ?? '';
        self::assertNotSame('', $etag);

        self::$client->request('GET', $this->getIriFromResource($quote) . '/pdf', ['headers' => ['accept' => 'application/pdf', 'if-none-match' => $etag]]);

        self::assertResponseStatusCodeSame(304);
    }

    public function testPdfOfAnotherCompanyIsNotFound(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignQuote = QuoteFactory::createOne(['client' => ClientFactory::createOne(['company' => $otherCompany]), 'company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('GET', $this->getIriFromResource($foreignQuote) . '/pdf', ['headers' => ['accept' => 'application/pdf']]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testPdfRequiresAuthentication(): void
    {
        $quote = $this->createQuote();

        self::createClient()->request('GET', $this->getIriFromResource($quote) . '/pdf', ['headers' => ['accept' => 'application/pdf']]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLinks(): void
    {
        $quote = $this->createQuote();
        $uuid = $quote->getUuid()->toString();

        $data = $this->requestGet($this->getIriFromResource($quote));

        self::assertSame('http://localhost/view/quote/' . $uuid, $data['publicUrl']);
        self::assertSame('http://localhost/view/quote/' . $uuid . '.pdf', $data['pdfUrl']);
        self::assertArrayNotHasKey('payUrl', $data);

        $member = $this->requestGetCollection('/api/quotes')['member'][0];

        self::assertTrue(str_starts_with($member['publicUrl'], 'http://localhost/view/quote/'));
        self::assertTrue(str_starts_with($member['pdfUrl'], 'http://localhost/view/quote/'));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createQuote(array $attributes = []): Quote
    {
        $client = ClientFactory::createOne();

        return QuoteFactory::createOne([
            'client' => $client,
            'users' => ContactFactory::createMany(1, ['client' => $client]),
            'lines' => [new Line()->setName('Test Item')->setQty(1)->setPrice(10000)],
            ...$attributes,
        ]);
    }
}
