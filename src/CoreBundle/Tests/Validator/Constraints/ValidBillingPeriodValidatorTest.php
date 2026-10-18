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

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Entity\BillingPeriod;
use SolidInvoice\CoreBundle\Validator\Constraints\ValidBillingPeriod;
use SolidInvoice\CoreBundle\Validator\Constraints\ValidBillingPeriodValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ValidBillingPeriodValidator>
 */
#[CoversClass(ValidBillingPeriod::class)]
#[CoversClass(ValidBillingPeriodValidator::class)]
final class ValidBillingPeriodValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidBillingPeriodValidator
    {
        return new ValidBillingPeriodValidator();
    }

    public function testEmptyPeriodPasses(): void
    {
        $this->validator->validate(BillingPeriod::of(null, null), new ValidBillingPeriod());

        $this->assertNoViolation();
    }

    public function testNullValuePasses(): void
    {
        $this->validator->validate(null, new ValidBillingPeriod());

        $this->assertNoViolation();
    }

    public function testEqualStartAndEndPasses(): void
    {
        $date = CarbonImmutable::parse('2026-02-01');

        $this->validator->validate(BillingPeriod::of($date, $date), new ValidBillingPeriod());

        $this->assertNoViolation();
    }

    public function testStartOnlyFails(): void
    {
        $constraint = new ValidBillingPeriod();

        $this->validator->validate(BillingPeriod::of(CarbonImmutable::parse('2026-02-01'), null), $constraint);

        $this->buildViolation($constraint->incompleteMessage)->assertRaised();
    }

    public function testEndOnlyFails(): void
    {
        $constraint = new ValidBillingPeriod();

        $this->validator->validate(BillingPeriod::of(null, CarbonImmutable::parse('2026-02-28')), $constraint);

        $this->buildViolation($constraint->incompleteMessage)->assertRaised();
    }

    public function testInvertedPeriodFails(): void
    {
        $constraint = new ValidBillingPeriod();

        $this->validator->validate(
            BillingPeriod::of(CarbonImmutable::parse('2026-02-28'), CarbonImmutable::parse('2026-02-01')),
            $constraint,
        );

        $this->buildViolation($constraint->invertedMessage)->assertRaised();
    }
}
