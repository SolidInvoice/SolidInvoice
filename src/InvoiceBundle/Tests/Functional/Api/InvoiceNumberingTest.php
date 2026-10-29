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

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\ApiResource\NextNumber;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\HttpFoundation\Response;
use function array_map;

#[Group('functional')]
final class InvoiceNumberingTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testABlankInvoiceNumberIsGeneratedOnCreate(): void
    {
        $this->createInvoice(['invoiceId' => '41']);

        $result = $this->requestPost('/api/invoices', $this->invoicePayload());

        self::assertSame('42', $result['invoiceId']);
    }

    public function testASuppliedInvoiceNumberIsKept(): void
    {
        $result = $this->requestPost('/api/invoices', $this->invoicePayload(['invoiceId' => 'INV-CUSTOM-9']));

        self::assertSame('INV-CUSTOM-9', $result['invoiceId']);
    }

    public function testADuplicateInvoiceNumberIsRejected(): void
    {
        $this->createInvoice(['invoiceId' => 'INV-1']);

        $this->assertDuplicateRejected('INV-1');
    }

    public function testAnArchivedInvoiceKeepsItsNumber(): void
    {
        $this->createInvoice(['invoiceId' => 'INV-ARCHIVED', 'archived' => true]);

        $this->assertDuplicateRejected('INV-ARCHIVED');
    }

    public function testAnotherCompanysInvoiceNumberCanBeReused(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        InvoiceFactory::createOne(['client' => ClientFactory::createOne(['company' => $otherCompany]), 'invoiceId' => 'INV-SHARED']);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $result = $this->requestPost('/api/invoices', $this->invoicePayload(['invoiceId' => 'INV-SHARED']));

        self::assertSame('INV-SHARED', $result['invoiceId']);
    }

    public function testNextNumberPreviewsWithoutReserving(): void
    {
        $this->createInvoice(['invoiceId' => '7']);

        self::assertSame(['type' => 'invoice', 'number' => '8'], $this->nextNumber('invoice'));
        self::assertSame(['type' => 'invoice', 'number' => '8'], $this->nextNumber('invoice'));

        $result = $this->requestPost('/api/invoices', $this->invoicePayload());

        self::assertSame('8', $result['invoiceId']);
        self::assertSame(['type' => 'invoice', 'number' => '9'], $this->nextNumber('invoice'));
    }

    public function testNextNumberForQuotes(): void
    {
        QuoteFactory::createOne(['quoteId' => '15', 'status' => QuoteStatus::Draft]);

        self::assertSame(['type' => 'quote', 'number' => '16'], $this->nextNumber('quote'));
    }

    public function testNextNumberRejectsAnUnknownType(): void
    {
        self::$client->request('GET', '/api/next-number?type=receipt', ['headers' => ['accept' => 'application/ld+json']]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testNextNumberRequiresAType(): void
    {
        self::$client->request('GET', '/api/next-number', ['headers' => ['accept' => 'application/ld+json']]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testNextNumberRequiresAuthentication(): void
    {
        self::$client->request('GET', '/api/next-number?type=invoice', ['headers' => ['accept' => 'application/ld+json', 'X-API-TOKEN' => '']]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testConvertingAQuoteNumbersTheInvoice(): void
    {
        $this->createInvoice(['invoiceId' => '20']);
        $client = ClientFactory::createOne();
        $quote = QuoteFactory::createOne([
            'status' => QuoteStatus::Draft,
            'client' => $client,
            'users' => ContactFactory::createMany(1, ['client' => $client]),
            'lines' => [new QuoteLine()->setName('Item')->setQty(1)->setPrice(10000)],
        ]);

        $result = $this->requestPostExpecting(sprintf('/api/quotes/%s/invoice', $quote->getId()), [], Invoice::class);

        self::assertSame('21', $result['invoiceId']);
    }

    public function testGeneratingFromARecurringInvoiceNumbersTheInvoice(): void
    {
        $this->createInvoice(['invoiceId' => '30']);
        $client = ClientFactory::createOne();
        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Active,
            'client' => $client,
            'users' => ContactFactory::createMany(1, ['client' => $client]),
            'lines' => [new RecurringInvoiceLine()->setName('Item')->setQty(1)->setPrice(10000)],
        ]);

        $result = $this->requestPostExpecting(sprintf('/api/recurring-invoices/%s/generate', $recurringInvoice->getId()), [], Invoice::class);

        self::assertSame('31', $result['invoiceId']);
    }

    public function testCloneInvoice(): void
    {
        $source = $this->requestPost('/api/invoices', $this->invoicePayload([
            'invoiceId' => '50',
            'invoiceDate' => '2020-01-01',
            'paymentTerms' => 'net_14',
            'notes' => 'Cloned notes',
        ]));

        $clone = $this->requestPost(sprintf('/api/invoices/%s/clone', $source['id']), []);

        self::assertNotSame($source['id'], $clone['id']);
        self::assertSame('51', $clone['invoiceId']);
        self::assertSame('draft', $clone['status']);
        self::assertStringStartsWith(CarbonImmutable::today()->format('Y-m-d'), $clone['invoiceDate']);
        self::assertStringStartsWith(CarbonImmutable::today()->addDays(14)->format('Y-m-d'), $clone['due']);
        self::assertSame('net_14', $clone['paymentTerms']);
        self::assertSame('Cloned notes', $clone['notes']);
        self::assertSame($source['client'], $clone['client']);
        self::assertSame($source['users'], $clone['users']);
        self::assertSame($source['total'], $clone['total']);
        self::assertSame($source['baseTotal'], $clone['baseTotal']);
        self::assertSame($source['total'], $clone['balance']);
        self::assertSame($this->lineValues($source['lines']), $this->lineValues($clone['lines']));
        self::assertNotSame($source['lines'][0]['id'], $clone['lines'][0]['id']);
    }

    public function testCloneOfAnotherCompanysInvoiceIsNotFound(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignInvoice = InvoiceFactory::createOne(['client' => ClientFactory::createOne(['company' => $otherCompany])]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('POST', sprintf('/api/invoices/%s/clone', $foreignInvoice->getId()), [
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function assertDuplicateRejected(string $invoiceId): void
    {
        $response = self::$client->request('POST', '/api/invoices', [
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
            'json' => $this->invoicePayload(['invoiceId' => $invoiceId]),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame(
            [['propertyPath' => 'invoiceId', 'message' => 'This invoice number is already in use.']],
            array_map(
                static fn (array $violation): array => ['propertyPath' => $violation['propertyPath'], 'message' => $violation['message']],
                $response->toArray(false)['violations'],
            ),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createInvoice(array $attributes): void
    {
        InvoiceFactory::createOne($attributes);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function invoicePayload(array $overrides = []): array
    {
        // ClientFactory picks a random company, and some tests here create a second one.
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
     * @return array<string, mixed>
     */
    private function nextNumber(string $type): array
    {
        // requestGet() would check the body against the Invoice schema.
        $result = self::$client->request('GET', '/api/next-number?type=' . $type, ['headers' => ['accept' => 'application/ld+json']])->toArray();
        self::assertMatchesResourceItemJsonSchema(NextNumber::class);

        return ['type' => $result['type'], 'number' => $result['number']];
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
