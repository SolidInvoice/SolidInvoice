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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * End-to-end proof for SOL-5: two separate accounts cannot see each other's data.
 *
 * Each "account" is an independent tenant — its own {@see Company}, its own {@see User}
 * (member of that company only), and its own {@see Client}/{@see Invoice}. The isolation
 * is enforced by the Doctrine {@see \SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter},
 * scoped per request by {@see \SolidInvoice\CoreBundle\Listener\CompanyEventSubscriber}.
 *
 * This covers the two ways one tenant could reach another's data:
 *  - reading records (repository queries and direct object URLs), and
 *  - switching the active tenant to a company you don't belong to.
 */
#[Group('functional')]
final class AccountIsolationTest extends WebTestCase
{
    use HasBrowser;
    use DoctrineTestTrait;

    /**
     * The Doctrine filter hides foreign-company rows from repository queries and from
     * lookups by id, in both directions. This is the mechanism the HTTP layer relies on.
     */
    public function testRepositoriesOnlyReturnTheActiveCompanyData(): void
    {
        $alpha = $this->seedAccount('alpha');
        $beta = $this->seedAccount('beta');

        // Start from a cold identity map so lookups hit the database (and the filter),
        // instead of returning objects cached during seeding.
        $this->em->clear();

        $selector = self::getContainer()->get(CompanySelector::class);
        self::assertInstanceOf(CompanySelector::class, $selector);

        // --- Active tenant: Alpha ---
        $selector->switchCompany($alpha['company']->getId());

        $clients = $this->em->getRepository(Client::class)->findAll();
        self::assertCount(1, $clients, 'Alpha must only see its own client.');
        self::assertSame('alpha Client', $clients[0]->getName());

        $invoices = $this->em->getRepository(Invoice::class)->findAll();
        self::assertCount(1, $invoices, 'Alpha must only see its own invoice.');
        self::assertTrue($invoices[0]->getId()->equals($alpha['invoice']->getId()));

        // Beta's records are invisible even when addressed directly by id.
        self::assertNull(
            $this->em->getRepository(Client::class)->findOneBy(['id' => $beta['client']->getId()]),
            "Alpha must not be able to load Beta's client by id."
        );
        self::assertNull(
            $this->em->getRepository(Invoice::class)->findOneBy(['id' => $beta['invoice']->getId()]),
            "Alpha must not be able to load Beta's invoice by id."
        );

        // --- Active tenant: Beta (symmetry) ---
        $this->em->clear();
        $selector->switchCompany($beta['company']->getId());

        $clients = $this->em->getRepository(Client::class)->findAll();
        self::assertCount(1, $clients, 'Beta must only see its own client.');
        self::assertSame('beta Client', $clients[0]->getName());

        self::assertNull(
            $this->em->getRepository(Invoice::class)->findOneBy(['id' => $alpha['invoice']->getId()]),
            "Beta must not be able to load Alpha's invoice by id."
        );
    }

    /**
     * Over real HTTP, an authenticated user of Alpha gets a 404 when opening Beta's
     * invoice URL, while their own invoice opens fine.
     */
    public function testUserCannotOpenAnotherCompanysInvoiceOverHttp(): void
    {
        $alpha = $this->seedAccount('alpha');
        $beta = $this->seedAccount('beta');

        $userA = $alpha['user'];
        $ownInvoiceId = $alpha['invoice']->getId()->toString();
        $foreignInvoiceId = $beta['invoice']->getId()->toString();

        $this->em->clear();

        $this->browser()
            ->actingAs($userA)
            ->visit('/invoices/view/' . $ownInvoiceId)
            ->assertSuccessful()
            ->visit('/invoices/view/' . $foreignInvoiceId)
            ->assertStatus(404);
    }

    /**
     * Same guarantee for the client detail page.
     */
    public function testUserCannotOpenAnotherCompanysClientOverHttp(): void
    {
        $alpha = $this->seedAccount('alpha');
        $beta = $this->seedAccount('beta');

        $userA = $alpha['user'];
        $ownClientId = $alpha['client']->getId()->toString();
        $foreignClientId = $beta['client']->getId()->toString();

        $this->em->clear();

        $this->browser()
            ->actingAs($userA)
            ->visit('/clients/view/' . $ownClientId)
            ->assertSuccessful()
            ->visit('/clients/view/' . $foreignClientId)
            ->assertStatus(404);
    }

    /**
     * A user cannot make another company their active tenant: switching to a company
     * they are not a member of is rejected outright.
     */
    public function testUserCannotSwitchIntoACompanyTheyDoNotBelongTo(): void
    {
        $alpha = $this->seedAccount('alpha');
        $beta = $this->seedAccount('beta');

        $userA = $alpha['user'];
        $foreignCompanyId = $beta['company']->getId()->toString();

        $this->em->clear();

        $this->browser()
            ->actingAs($userA)
            ->visit('/select-company/' . $foreignCompanyId)
            ->assertStatus(400);
    }

    /**
     * Creates a self-contained tenant: a company, a user that belongs only to it, and a
     * client + invoice owned by it. The company filter is disabled while seeding so both
     * tenants can be written regardless of which one is currently active.
     *
     * @return array{company: Company, user: User, client: Client, invoice: Invoice}
     */
    private function seedAccount(string $slug): array
    {
        $filters = $this->em->getFilters();
        $wasEnabled = $filters->isEnabled('company');

        if ($wasEnabled) {
            $filters->disable('company');
        }

        $company = CompanyFactory::createOne(['name' => $slug . ' Inc']);

        $user = UserFactory::createOne([
            'email' => $slug . '@isolation.test',
            'companies' => [$company],
        ]);

        $client = ClientFactory::createOne([
            'company' => $company,
            'name' => $slug . ' Client',
            'status' => ClientStatus::Active,
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        if ($wasEnabled) {
            $filters->enable('company');
        }

        return [
            'company' => $company,
            'user' => $user,
            'client' => $client,
            'invoice' => $invoice,
        ];
    }
}
