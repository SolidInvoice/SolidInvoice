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

use Brick\Math\BigDecimal;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Validator\Constraints\LineDiscountWithinLineTotalValidatorTest
 */
final class LineDiscountWithinLineTotalValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof LineDiscountWithinLineTotal) {
            throw new UnexpectedTypeException($constraint, LineDiscountWithinLineTotal::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof Discount) {
            throw new UnexpectedValueException($value, Discount::class);
        }

        if ($value->getReason() !== null && mb_strlen($value->getReason()) > 255) {
            $this->context->buildViolation($constraint->reasonTooLongMessage)
                ->atPath('reason')
                ->setParameter('{{ limit }}', '255')
                ->addViolation();
        }

        if ($value->getType() === Discount::TYPE_PERCENTAGE) {
            $percentage = $value->getValuePercentage();

            if ($percentage !== null && ($percentage < 0.0 || $percentage > 100.0)) {
                $this->context->buildViolation($constraint->percentageRangeMessage)
                    ->atPath('valuePercentage')
                    ->addViolation();
            }

            return;
        }

        if ($value->getType() !== Discount::TYPE_MONEY) {
            return;
        }

        $owner = $this->context->getObject();

        if (! $owner instanceof LineInterface) {
            return;
        }

        $amount = BigDecimal::of($value->getValueMoney());

        if ($amount->isNegative() || $amount->isGreaterThan($owner->getGrossTotal())) {
            $this->context->buildViolation($constraint->exceedsLineTotalMessage)
                ->atPath('valueMoney')
                ->addViolation();
        }
    }
}
