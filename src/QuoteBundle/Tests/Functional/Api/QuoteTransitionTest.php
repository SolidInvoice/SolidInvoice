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

use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class QuoteTransitionTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Quote::class;
    }

    private function createQuoteWithContact(): Quote
    {
        $client = ClientFactory::createOne()->_real();
        $contact = ContactFactory::createOne(['client' => $client])->_real();
        $quote = QuoteFactory::createOne(['status' => QuoteStatus::Draft, 'client' => $client])->_real();
        $quote->addUser($contact);
        self::getContainer()->get('doctrine')->getManager()->flush();

        return $quote;
    }

    public function testSendQuote(): void
    {
        $quote = $this->createQuoteWithContact();

        $result = $this->requestPost(
            sprintf('/api/quotes/%s/transitions/send', $quote->getId()),
            []
        );

        self::assertSame('pending', $result['status']);
    }

    public function testAcceptQuote(): void
    {
        $quote = $this->createQuoteWithContact();

        $quoteId = $quote->getId();

        // First transition: draft → pending
        $this->requestPost(
            sprintf('/api/quotes/%s/transitions/send', $quoteId),
            []
        );

        // Second transition: pending → accepted
        $result = $this->requestPost(
            sprintf('/api/quotes/%s/transitions/accept', $quoteId),
            []
        );

        self::assertSame('accepted', $result['status']);
    }

    public function testDeclineQuote(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $quote = QuoteFactory::createOne([
            'status' => QuoteStatus::Draft,
            'users' => $contacts,
        ])->_real();

        $result = $this->requestPost(
            sprintf('/api/quotes/%s/transitions/decline', $quote->getId()),
            []
        );

        self::assertSame('declined', $result['status']);
    }

    public function testInvalidTransition(): void
    {
        $quote = QuoteFactory::createOne(['status' => QuoteStatus::Draft])->_real();

        self::$client->request('POST', sprintf('/api/quotes/%s/transitions/accept', $quote->getId()), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
            'json' => [],
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testConvertQuoteToInvoice(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $quote = QuoteFactory::createOne([
            'status' => QuoteStatus::Draft,
            'users' => $contacts,
        ])->_real();

        $result = $this->requestPostExpecting(
            sprintf('/api/quotes/%s/invoice', $quote->getId()),
            [],
            Invoice::class
        );

        self::assertSame('Invoice', $result['@type']);
        self::assertTrue(Ulid::isValid($result['id']));
    }

    public function testCannotConvertTwice(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $quote = QuoteFactory::createOne([
            'status' => QuoteStatus::Draft,
            'users' => $contacts,
        ])->_real();

        $quoteId = $quote->getId();

        // First conversion succeeds
        $this->requestPostExpecting(
            sprintf('/api/quotes/%s/invoice', $quoteId),
            [],
            Invoice::class
        );

        // Second conversion should fail with 422
        self::$client->request('POST', sprintf('/api/quotes/%s/invoice', $quoteId), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
            'json' => [],
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTransitionOnForeignCompanyQuote(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignQuote = QuoteFactory::createOne(['company' => $otherCompany, 'status' => QuoteStatus::Draft])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('POST', sprintf('/api/quotes/%s/transitions/send', $foreignQuote->getId()), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
            'json' => [],
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testConvertForeignCompanyQuote(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignQuote = QuoteFactory::createOne(['company' => $otherCompany, 'status' => QuoteStatus::Draft])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('POST', sprintf('/api/quotes/%s/invoice', $foreignQuote->getId()), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
            'json' => [],
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
