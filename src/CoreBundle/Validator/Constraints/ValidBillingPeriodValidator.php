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

namespace SolidInvoice\CoreBundle\Validator\Constraints;

use SolidInvoice\CoreBundle\Entity\BillingPeriod;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Validator\Constraints\ValidBillingPeriodValidatorTest
 */
final class ValidBillingPeriodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ValidBillingPeriod) {
            throw new UnexpectedTypeException($constraint, ValidBillingPeriod::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof BillingPeriod) {
            throw new UnexpectedValueException($value, BillingPeriod::class);
        }

        // No period is the normal, default case and must pass.
        if ($value->isEmpty()) {
            return;
        }

        $start = $value->getStart();
        $end = $value->getEnd();

        if ($start === null || $end === null) {
            $this->context->buildViolation($constraint->incompleteMessage)->addViolation();

            return;
        }

        if ($start > $end) {
            $this->context->buildViolation($constraint->invertedMessage)->addViolation();
        }
    }
}
