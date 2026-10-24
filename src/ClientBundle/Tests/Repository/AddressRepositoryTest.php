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

namespace SolidInvoice\ClientBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Repository\AddressRepository;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AddressRepository::class)]
final class AddressRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    private AddressRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(AddressRepository::class);
    }

    public function testFindCompanyAddressReturnsTheClientlessAddressForTheCompany(): void
    {
        // No client and no explicit company: CompanyListener (prePersist) stamps the
        // active CompanySelector company, same as production code creating an address.
        $address = new Address();
        $this->em->persist($address);
        $this->em->flush();

        self::assertSame($address, $this->repository->findCompanyAddress($this->company->getId()));
    }

    public function testFindCompanyAddressReturnsNullWhenOnlyAClientOwnedAddressExists(): void
    {
        $address = new Address();
        ClientFactory::createOne(['company' => $this->company, 'addresses' => [$address]]);

        self::assertNull($this->repository->findCompanyAddress($this->company->getId()));
    }

    public function testFindCompanyAddressIgnoresAnotherCompanysRowEvenWithTheCompanyFilterDisabled(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignAddress = new Address();
        $this->em->persist($foreignAddress);
        $this->em->flush();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $this->em->getFilters()->disable('company');

        try {
            self::assertNull($this->repository->findCompanyAddress($this->company->getId()));
        } finally {
            self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
        }
    }
}
