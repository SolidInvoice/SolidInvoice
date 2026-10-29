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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyEnabledFilter;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function array_map;

#[CoversClass(CompanyEnabledFilter::class)]
final class DisabledCompanyTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * The crons scan every tenant with the company filter disabled; a disabled company's
     * data must stay hidden from them too.
     */
    public function testADisabledCompanyAndItsDataAreHiddenWithTheCompanyFilterDisabled(): void
    {
        $disabled = CompanyFactory::createOne();
        $disabledInvoice = InvoiceFactory::createOne([
            'company' => $disabled,
            'client' => ClientFactory::createOne(['company' => $disabled]),
        ]);

        $enabledInvoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company]),
        ]);

        $user = UserFactory::createOne(['companies' => [$this->company, $disabled]]);

        $em = self::getContainer()->get(EntityManagerInterface::class);

        $em->createQueryBuilder()
            ->update(Company::class, 'c')
            ->set('c.enabled', ':enabled')
            ->where('c.id = :id')
            ->setParameter('enabled', false)
            ->setParameter('id', $disabled->getId(), UlidType::NAME)
            ->getQuery()
            ->execute();

        $em->clear();
        $em->getFilters()->disable('company');

        self::assertNull($em->find(Company::class, $disabled->getId()));
        self::assertNotNull($em->find(Company::class, $this->company->getId()));

        self::assertNull($em->find(Invoice::class, $disabledInvoice->getId()));
        self::assertNotNull($em->find(Invoice::class, $enabledInvoice->getId()));

        $user = $em->find(User::class, $user->getId());
        self::assertNotNull($user);
        self::assertSame(
            [$this->company->getId()->toBase58()],
            array_map(static fn (Company $company): string => $company->getId()->toBase58(), $user->getCompanies()->toArray()),
        );
    }
}
