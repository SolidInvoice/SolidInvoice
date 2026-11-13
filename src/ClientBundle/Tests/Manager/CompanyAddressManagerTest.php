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

namespace SolidInvoice\ClientBundle\Tests\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Manager\CompanyAddressManager;
use SolidInvoice\ClientBundle\Repository\AddressRepository;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @see CompanyAddressManager
 */
final class CompanyAddressManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AddressRepository & M\MockInterface $addressRepository;

    private CompanyRepository & M\MockInterface $companyRepository;

    private SystemConfig & M\MockInterface $systemConfig;

    private ValidatorInterface & M\MockInterface $validator;

    protected function setUp(): void
    {
        $this->addressRepository = M::mock(AddressRepository::class);
        $this->companyRepository = M::mock(CompanyRepository::class);
        $this->systemConfig = M::mock(SystemConfig::class);
        $this->validator = M::mock(ValidatorInterface::class);
        $this->validator->shouldReceive('validateProperty')->andReturn(new ConstraintViolationList())->byDefault();
    }

    private function manager(?Ulid $companyId): CompanyAddressManager
    {
        // CompanySelector is final, so tests build a real instance with a dummy
        // ManagerRegistry and set the private companyId directly (established
        // pattern, e.g. SaasBundle/Tests/Action/ChoosePlanActionTest).
        $selector = new CompanySelector(M::mock(ManagerRegistry::class));

        if ($companyId instanceof Ulid) {
            new ReflectionProperty(CompanySelector::class, 'companyId')->setValue($selector, $companyId);
        }

        return new CompanyAddressManager($this->addressRepository, $this->companyRepository, $selector, $this->validator, $this->systemConfig);
    }

    public function testGetReturnsThePersistedAddressRow(): void
    {
        $companyId = new Ulid();
        $address = new Address();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn($address);
        $this->systemConfig->shouldNotReceive('get');

        self::assertSame($address, $this->manager($companyId)->get());
    }

    public function testGetFallsBackToTheLegacySettingWhenNoRowExistsAndDoesNotPersistAnything(): void
    {
        $companyId = new Ulid();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn(null);
        $this->systemConfig->shouldReceive('get')
            ->once()
            ->with('system/company/contact_details/address')
            ->andReturn('{"street1":"123 Main St","street2":null,"city":"Cape Town","state":null,"zip":"8000","country":"ZA"}');
        $this->addressRepository->shouldNotReceive('save');
        $this->companyRepository->shouldNotReceive('find');

        $address = $this->manager($companyId)->get();

        self::assertInstanceOf(Address::class, $address);
        self::assertNull($address->getId());
        self::assertSame('123 Main St', $address->getStreet1());
        self::assertSame('Cape Town', $address->getCity());
        self::assertSame('ZA', $address->getCountry());
    }

    public function testGetReturnsNullWhenNoRowExistsAndTheSettingIsNull(): void
    {
        $companyId = new Ulid();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn(null);
        $this->systemConfig->shouldReceive('get')->once()->andReturn(null);

        self::assertNull($this->manager($companyId)->get());
    }

    public function testGetReturnsNullWhenNoRowExistsAndTheSettingIsMalformedJson(): void
    {
        $companyId = new Ulid();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn(null);
        $this->systemConfig->shouldReceive('get')->once()->andReturn('{"street1": "123 Main St"');

        self::assertNull($this->manager($companyId)->get());
    }

    public function testGetReturnsNullWhenNoRowExistsAndTheSettingIsABareJsonString(): void
    {
        $companyId = new Ulid();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn(null);
        // The same raw, unquoted shape DashboardBundle/Tests/Checklist/Items/ChecklistItemsTest
        // writes for this setting — a legacy value that predates the JSON encoding.
        $this->systemConfig->shouldReceive('get')->once()->andReturn('123 Main St');

        self::assertNull($this->manager($companyId)->get());
    }

    public function testGetReturnsNullWhenNoCompanyIsSelected(): void
    {
        $this->addressRepository->shouldNotReceive('findCompanyAddress');
        $this->systemConfig->shouldNotReceive('get');

        self::assertNull($this->manager(null)->get());
    }

    public function testGetReResolvesWhenTheSelectedCompanyChanges(): void
    {
        $companyA = new Ulid();
        $companyB = new Ulid();
        $addressA = new Address();
        $addressB = new Address();

        $selector = new CompanySelector(M::mock(ManagerRegistry::class));
        new ReflectionProperty(CompanySelector::class, 'companyId')->setValue($selector, $companyA);
        $manager = new CompanyAddressManager($this->addressRepository, $this->companyRepository, $selector, $this->validator, $this->systemConfig);

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyA)->andReturn($addressA);
        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyB)->andReturn($addressB);

        self::assertSame($addressA, $manager->get());

        new ReflectionProperty(CompanySelector::class, 'companyId')->setValue($selector, $companyB);

        self::assertSame($addressB, $manager->get());
    }

    public function testGetRetriesOnTheNextCallAfterATransientFailure(): void
    {
        $companyId = new Ulid();
        $address = new Address();

        $calls = 0;
        $this->addressRepository->shouldReceive('findCompanyAddress')
            ->twice()
            ->with($companyId)
            ->andReturnUsing(static function () use (&$calls, $address) {
                ++$calls;

                if (1 === $calls) {
                    throw new RuntimeException('connection lost');
                }

                return $address;
            });

        $manager = $this->manager($companyId);

        try {
            $manager->get();
            self::fail('Expected the first call to propagate the exception.');
        } catch (RuntimeException) {
            // Expected: the failed call must not cache anything.
        }

        self::assertSame($address, $manager->get());
    }

    public function testStoreCreatesARowWhenNoneExists(): void
    {
        $companyId = new Ulid();
        $company = new Company();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn(null);
        $this->companyRepository->shouldReceive('find')->once()->with($companyId)->andReturn($company);
        $this->addressRepository->shouldReceive('save')
            ->once()
            ->with(M::on(fn (Address $address): bool => $address->getCompany() === $company
                && '123 Main St' === $address->getStreet1()
                && 'Cape Town' === $address->getCity()
                && 'ZA' === $address->getCountry()));

        $this->manager($companyId)->store('{"street1":"123 Main St","city":"Cape Town","country":"ZA"}');
    }

    public function testStoreUpdatesTheExistingRowInPlaceRatherThanAppending(): void
    {
        $companyId = new Ulid();
        $address = new Address();
        $address->setStreet1('Old street');

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn($address);
        $this->companyRepository->shouldNotReceive('find');
        $this->addressRepository->shouldReceive('save')->once()->with($address);

        $this->manager($companyId)->store('{"street1":"New street","city":"Cape Town","country":"ZA"}');

        self::assertSame('New street', $address->getStreet1());
        self::assertSame('Cape Town', $address->getCity());
    }

    public function testStoreRemovesTheExistingRowOnAnEmptyPayload(): void
    {
        $companyId = new Ulid();
        $address = new Address();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn($address);
        $this->addressRepository->shouldReceive('remove')->once()->with($address);
        $this->addressRepository->shouldNotReceive('save');

        $this->manager($companyId)->store(null);
    }

    public function testStoreIsANoOpOnAMalformedPayload(): void
    {
        $companyId = new Ulid();

        $this->addressRepository->shouldNotReceive('findCompanyAddress');
        $this->companyRepository->shouldNotReceive('find');
        $this->addressRepository->shouldNotReceive('save');
        $this->addressRepository->shouldNotReceive('remove');

        $this->manager($companyId)->store('{"street1": not valid json');
    }

    public function testStoreUppercasesALowercaseLegacyCountryCode(): void
    {
        $companyId = new Ulid();
        $company = new Company();

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn(null);
        $this->companyRepository->shouldReceive('find')->once()->with($companyId)->andReturn($company);
        $this->addressRepository->shouldReceive('save')
            ->once()
            ->with(M::on(fn (Address $address): bool => 'ZA' === $address->getCountry()));

        $this->manager($companyId)->store('{"street1":"123 Main St","country":"za"}');
    }

    public function testStoreRejectsAnInvalidCountryAndDoesNotPersist(): void
    {
        $companyId = new Ulid();
        $address = new Address();

        $violation = new ConstraintViolation('This value is not a valid country.', null, [], $address, 'country', 'XX');
        $this->validator->shouldReceive('validateProperty')
            ->once()
            ->with($address, 'country')
            ->andReturn(new ConstraintViolationList([$violation]));

        $this->addressRepository->shouldReceive('findCompanyAddress')->once()->with($companyId)->andReturn($address);
        $this->addressRepository->shouldNotReceive('save');

        try {
            $this->manager($companyId)->store('{"street1":"123 Main St","country":"XX"}');
            self::fail('Expected a ValidationFailedException');
        } catch (ValidationFailedException) {
            // Expected: an invalid country must not be persisted.
        }
    }
}
