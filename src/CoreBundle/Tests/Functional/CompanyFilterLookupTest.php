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
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Executable specification for one question that keeps getting answered wrong:
 * does {@see \Doctrine\ORM\EntityRepository::find()} apply the CompanyFilter?
 *
 * It does. `find()` is not a filter bypass, and swapping it for `findOneBy(['id' => ...])`
 * is not a cross-tenant fix. The one and only difference between them is the identity map.
 *
 * The paths, in the vendor code this repo has installed:
 *
 *  - `EntityRepository::find()` (vendor/doctrine/orm/src/EntityRepository.php:86) delegates to
 *    `EntityManager::find()` (vendor/doctrine/orm/src/EntityManager.php:274).
 *  - `EntityManager::find()` checks the identity map first
 *    (vendor/doctrine/orm/src/EntityManager.php:325-347). On a hit it returns the managed
 *    object and never touches the database, so no filter can run. This is the only bypass.
 *  - On a miss it calls `loadById()` -> `load()`
 *    (vendor/doctrine/orm/src/Persisters/Entity/BasicEntityPersister.php:754, 725), which builds
 *    its SQL with `getSelectSQL()` (:1109). That method appends every enabled filter's
 *    constraint at :1146 and ANDs it into the WHERE at :1149-1153.
 *  - `EntityRepository::findOneBy()` (EntityRepository.php:125) calls the same `load()`, so it
 *    reaches the same `getSelectSQL()` and the same filter. It has no identity-map shortcut,
 *    which is why it is unaffected by a warm identity map.
 *
 * Every other read path lands on `getSelectSQL()` too — `refresh()` (:851), `loadAll()` (:884),
 * `loadCriteria()` (:961) and the collection loads (:1103, :1869) — and DQL applies filters in
 * `SqlWalker::generateFilterConditionSQL()` (vendor/doctrine/orm/src/Query/SqlWalker.php:454).
 *
 * @see \SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter
 * @see \SolidInvoice\CoreBundle\Tests\Functional\AccountIsolationTest
 */
#[Group('functional')]
final class CompanyFilterLookupTest extends WebTestCase
{
    use DoctrineTestTrait;

    /**
     * The claim under test: with nothing in the identity map — the shape of a real request —
     * `find()` filters exactly like `findOneBy()`, and neither returns another company's row.
     */
    public function testFindAppliesTheCompanyFilterWhenItReachesTheDatabase(): void
    {
        [$alpha, $beta] = $this->seedTwoCompanies();

        // A real HTTP request starts here: nothing of either tenant is managed yet.
        $this->em->clear();
        $this->switchTo($alpha['company']);

        $invoices = $this->em->getRepository(Invoice::class);

        self::assertNull(
            $invoices->find($beta['invoice']->getId()),
            "find() must not return another company's invoice: the filter is in the WHERE clause."
        );
        self::assertNull(
            $invoices->findOneBy(['id' => $beta['invoice']->getId()]),
            'findOneBy() must not return it either — same load(), same filter.'
        );

        self::assertNotNull(
            $invoices->find($alpha['invoice']->getId()),
            "The active company's own invoice must still load, or the test proves nothing."
        );
    }

    /**
     * The only real difference. An entity already managed by the EntityManager is returned by
     * `find()` from the identity map, without SQL and therefore without the filter, while
     * `findOneBy()` still issues a filtered query and finds nothing.
     *
     * This is what makes `find()` look like a filter bypass in a functional test: the test
     * seeds both tenants through the same EntityManager, so the foreign row is already managed
     * before the request runs. Clearing the identity map — the last assertion — makes `find()`
     * agree with `findOneBy()` again, which is the proof that the filter was never the variable.
     */
    public function testFindReturnsAnAlreadyManagedEntityWithoutConsultingTheFilter(): void
    {
        [$alpha, $beta] = $this->seedTwoCompanies();

        // Deliberately no clear(): both tenants are still managed from seeding.
        $this->switchTo($alpha['company']);

        $invoices = $this->em->getRepository(Invoice::class);

        self::assertNotNull(
            $invoices->find($beta['invoice']->getId()),
            'An identity-map hit returns before any query is built (EntityManager.php:325-347).'
        );
        self::assertNull(
            $invoices->findOneBy(['id' => $beta['invoice']->getId()]),
            'findOneBy() has no such shortcut, so the filtered query returns nothing.'
        );

        $this->em->clear();

        self::assertNull(
            $invoices->find($beta['invoice']->getId()),
            'With the identity map cleared, find() filters too. The identity map was the difference.'
        );
    }

    private function switchTo(Company $company): void
    {
        $selector = self::getContainer()->get(CompanySelector::class);
        self::assertInstanceOf(CompanySelector::class, $selector);

        $selector->switchCompany($company->getId());
    }

    /**
     * @return array{0: array{company: Company, invoice: Invoice}, 1: array{company: Company, invoice: Invoice}}
     */
    private function seedTwoCompanies(): array
    {
        $filters = $this->em->getFilters();
        $wasEnabled = $filters->isEnabled('company');

        if ($wasEnabled) {
            $filters->disable('company');
        }

        $seed = static function (string $slug): array {
            $company = CompanyFactory::createOne(['name' => $slug . ' Inc']);

            $client = ClientFactory::createOne([
                'company' => $company,
                'name' => $slug . ' Client',
                'status' => ClientStatus::Active,
            ]);

            return [
                'company' => $company,
                'invoice' => InvoiceFactory::createOne([
                    'company' => $company,
                    'client' => $client,
                    'status' => InvoiceStatus::Pending,
                ]),
            ];
        };

        $accounts = [$seed('alpha'), $seed('beta')];

        if ($wasEnabled) {
            $filters->enable('company');
        }

        return $accounts;
    }
}
