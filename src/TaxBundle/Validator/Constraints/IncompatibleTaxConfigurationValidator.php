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

namespace SolidInvoice\TaxBundle\Validator\Constraints;

use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @see \SolidInvoice\TaxBundle\Tests\Validator\Constraints\IncompatibleTaxConfigurationValidatorTest
 */
final class IncompatibleTaxConfigurationValidator extends ConstraintValidator
{
    /**
     * @param Tax|LineTax|mixed $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof IncompatibleTaxConfiguration) {
            throw new UnexpectedTypeException($constraint, IncompatibleTaxConfiguration::class);
        }

        [$type, $compound] = match (true) {
            $value instanceof Tax => [
                $value->getType() !== null ? TaxType::tryFrom($value->getType()) : null,
                $value->isCompound(),
            ],
            $value instanceof LineTax => [
                $value->getTypeSnapshot(),
                $value->isCompound(),
            ],
            default => throw new UnexpectedValueException($value, Tax::class . '|' . LineTax::class),
        };

        if ($type === null || ! $compound) {
            return;
        }

        if ($type === TaxType::Inclusive) {
            $this->context->buildViolation($constraint->inclusiveCompoundMessage)
                ->atPath('compound')
                ->addViolation();

            return;
        }

        if ($type === TaxType::FlatRate) {
            $this->context->buildViolation($constraint->flatRateCompoundMessage)
                ->atPath('compound')
                ->addViolation();
        }
    }
}
