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

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use function array_column;
use function array_keys;

/**
 * Without Meilisearch configured (as in the test environment), search falls back to the database.
 */
#[Group('functional')]
final class SearchTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testFindsClientsContactsAndDocumentsOfThisCompanyOnly(): void
    {
        $smith = ClientFactory::createOne(['company' => $this->company, 'name' => 'Smith Plumbing']);
        ClientFactory::createOne(['company' => $this->company, 'name' => 'Naidoo Electrical']);
        ContactFactory::createOne(['client' => $smith, 'company' => $this->company, 'firstName' => 'Jane', 'lastName' => 'Smithers', 'email' => 'jane@example.com']);
        InvoiceFactory::createOne(['client' => $smith, 'invoiceId' => 'INV-0040', 'status' => InvoiceStatus::Overdue]);
        InvoiceFactory::createOne(['client' => $smith, 'invoiceId' => 'INV-0041', 'status' => InvoiceStatus::Paid]);
        QuoteFactory::createOne(['client' => $smith, 'quoteId' => 'Q-0011']);

        $other = CompanyFactory::createOne();
        self::getContainer()->get(CompanySelector::class)->switchCompany($other->getId());
        ClientFactory::createOne(['company' => $other, 'name' => 'Smith Elsewhere']);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $results = self::$client->request('GET', '/api/search?q=smi')->toArray();

        self::assertSame(['Smith Plumbing'], array_column($results['clients'], 'title'));
        self::assertSame('/api/clients/' . $smith->getId(), $results['clients'][0]['iri']);
        self::assertSame(['Jane Smithers'], array_column($results['contacts'], 'title'));
        self::assertSame('/api/clients/' . $smith->getId(), $results['contacts'][0]['iri']);
        self::assertEqualsCanonicalizing(['INV-0040', 'INV-0041'], array_column($results['invoices'], 'title'));
        self::assertSame(['Q-0011'], array_column($results['quotes'], 'title'));

        $overdue = self::$client->request('GET', '/api/search?q=smi%20status:overdue&types=invoices')->toArray();
        self::assertSame(['invoices'], array_keys($overdue));
        self::assertSame(['INV-0040'], array_column($overdue['invoices'], 'title'));

        self::$client->request('GET', '/api/search?q=s');
        self::assertResponseStatusCodeSame(400);
    }
}
