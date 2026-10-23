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

final class CompanyNameLengthTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testNameWithinLimitIsValid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'name', str_repeat('a', 45));

        self::assertCount(0, $violations);
    }

    public function testNameOverLimitIsInvalid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'name', str_repeat('a', 46));

        self::assertCount(1, $violations);
        self::assertSame('core.company.name.max_length', $violations->get(0)->getMessageTemplate());
    }

    public function testNameUnderMinimumIsInvalid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'name', 'ab');

        self::assertCount(1, $violations);
        self::assertSame('core.company.name.min_length', $violations->get(0)->getMessageTemplate());
    }
}
