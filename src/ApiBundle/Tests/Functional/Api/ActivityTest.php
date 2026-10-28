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

namespace SolidInvoice\ApiBundle\Tests\Functional\Api;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use function array_column;

#[Group('functional')]
final class ActivityTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testTheFeedIsNewestFirstAndCanBeNarrowedToAClient(): void
    {
        $smith = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'ZAR', 'name' => 'Smith', 'created' => CarbonImmutable::now()->subDays(10)]);
        $naidoo = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'ZAR', 'name' => 'Naidoo', 'created' => CarbonImmutable::now()->subDays(9)]);

        $invoice = InvoiceFactory::createOne(['client' => $smith, 'status' => InvoiceStatus::Paid, 'invoiceId' => 'INV-7']);
        PaymentFactory::createOne([
            'client' => $smith,
            'invoice' => $invoice,
            'method' => PaymentMethodFactory::createOne(['company' => $this->company, 'name' => 'Cash']),
            'totalAmount' => 15000,
            'currencyCode' => 'ZAR',
            'status' => PaymentStatus::Captured,
            'completed' => CarbonImmutable::now()->subHour(),
        ]);
        QuoteFactory::createOne(['client' => $naidoo, 'status' => QuoteStatus::Accepted, 'quoteId' => 'Q-3', 'updated' => CarbonImmutable::now()]);

        // Another company's activity never shows.
        $other = CompanyFactory::createOne();
        self::getContainer()->get(CompanySelector::class)->switchCompany($other->getId());
        ClientFactory::createOne(['company' => $other, 'name' => 'Elsewhere']);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $feed = self::$client->request('GET', '/api/activity')->toArray();

        $types = array_column($feed['member'], 'type');
        self::assertSame(['quote_accepted', 'payment_received', 'client_created', 'client_created'], $types);
        self::assertSame(['Q-3', 'INV-7', 'Naidoo', 'Smith'], array_column(array_column($feed['member'], 'subject'), 'label'));
        self::assertSame('150.00', $feed['member'][1]['amount']);
        self::assertSame('ZAR', $feed['member'][1]['currency']);
        self::assertSame('Cash', $feed['member'][1]['method']);
        self::assertSame('/api/invoices/' . $invoice->getId(), $feed['member'][1]['subject']['iri']);
        self::assertNull($feed['next']);

        $smithFeed = self::$client->request('GET', '/api/clients/' . $smith->getId() . '/activity')->toArray();
        self::assertSame(['payment_received', 'client_created'], array_column($smithFeed['member'], 'type'));

        self::$client->request('GET', '/api/activity?page=0');
        self::assertResponseStatusCodeSame(400);
    }
}
