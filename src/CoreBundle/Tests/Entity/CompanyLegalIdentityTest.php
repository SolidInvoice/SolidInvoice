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

namespace SolidInvoice\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function str_repeat;

final class CompanyLegalIdentityTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testEveryLegalIdentityFieldIsOptional(): void
    {
        self::assertCount(0, $this->validator->validatePropertyValue(Company::class, 'legalName', null));
        self::assertCount(0, $this->validator->validatePropertyValue(Company::class, 'registrationNumber', null));
        self::assertCount(0, $this->validator->validatePropertyValue(Company::class, 'legalForm', null));
        self::assertCount(0, $this->validator->validatePropertyValue(Company::class, 'additionalLegalInfo', null));
    }

    public function testLegalNameLongerThanTheCompanyNameCapIsValid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'legalName', str_repeat('a', 255));

        self::assertCount(0, $violations);
    }

    public function testLegalNameOverItsOwnLimitIsInvalid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'legalName', str_repeat('a', 256));

        self::assertCount(1, $violations);
    }

    public function testGettersAndSettersRoundTrip(): void
    {
        $company = new Company();
        $company->setLegalName('SolidWorx (Pty) Ltd');
        $company->setRegistrationNumber('2024/123456/07');
        $company->setLegalForm('Pty Ltd');
        $company->setAdditionalLegalInfo('Registered office: 1 Main Street');

        self::assertSame('SolidWorx (Pty) Ltd', $company->getLegalName());
        self::assertSame('2024/123456/07', $company->getRegistrationNumber());
        self::assertSame('Pty Ltd', $company->getLegalForm());
        self::assertSame('Registered office: 1 Main Street', $company->getAdditionalLegalInfo());
    }
}
