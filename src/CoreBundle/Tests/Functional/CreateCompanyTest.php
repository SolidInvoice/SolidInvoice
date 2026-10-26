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
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

#[Group('functional')]
final class CreateCompanyTest extends WebTestCase
{
    use HasBrowser;
    use DoctrineTestTrait;
    use EnsureApplicationInstalled;

    public function testCreatingACompanyRecordsItsOwnerCountryAndVatRates(): void
    {
        $user = new User();
        $user->setEmail('owner@example.com');
        $user->setEnabled(true);
        $user->setVerified(true);
        $user->setPassword('unused');

        $this->em->persist($user);
        $this->em->flush();

        $this->browser()
            ->actingAs($user)
            ->visit('/create-company')
            ->assertSuccessful()
            ->fillField('company[name]', 'Gezellig BV')
            ->selectFieldOption('company[country]', 'NL')
            ->selectFieldOption('company[currency]', 'EUR')
            ->interceptRedirects()
            ->click('Create Company')
            ->assertRedirected();

        $company = $this->em->getRepository(Company::class)->findOneBy(['name' => 'Gezellig BV']);

        self::assertInstanceOf(Company::class, $company);
        self::assertSame('NL', $company->getCountry());
        self::assertSame($user->getId()->toString(), $company->getOwner()?->getId()->toString());
        self::assertNotNull($company->getCreated());

        // Read past the company filter, which is still scoped to the installer's company.
        $taxes = $this->em->getConnection()->createQueryBuilder()
            ->select('name', 'rate')
            ->from(Tax::TABLE_NAME)
            ->where('company_id = :company')
            ->orderBy('rate', 'DESC')
            ->setParameter('company', $company->getId(), UlidType::NAME)
            ->executeQuery()
            ->fetchAllKeyValue();

        self::assertEquals(['VAT 21%' => 21, 'VAT 9%' => 9], $taxes);
    }
}
