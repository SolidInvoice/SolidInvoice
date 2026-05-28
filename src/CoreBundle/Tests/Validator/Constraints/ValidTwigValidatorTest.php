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

namespace SolidInvoice\CoreBundle\Tests\Validator\Constraints;

use SolidInvoice\CoreBundle\Validator\Constraints\ValidTwig;
use SolidInvoice\CoreBundle\Validator\Constraints\ValidTwigValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ValidTwigValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidTwigValidator
    {
        return new ValidTwigValidator();
    }

    public function testNullValuesAreSkipped(): void
    {
        $this->validator->validate(null, new ValidTwig());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsSkipped(): void
    {
        $this->validator->validate('', new ValidTwig());

        $this->assertNoViolation();
    }

    public function testValidTemplatePasses(): void
    {
        $this->validator->validate('Hello {{ name }}', new ValidTwig());

        $this->assertNoViolation();
    }

    public function testInvalidTwigSurfacesAsViolation(): void
    {
        $constraint = new ValidTwig();

        $this->validator->validate('{% if foo %}', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ error }}', 'Unexpected end of template.')
            ->setCode('valid-twig')
            ->assertRaised();
    }

    public function testRejectsNonStringValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(42, new ValidTwig());
    }

    public function testRejectsWrongConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('value', new NotNull());
    }
}
