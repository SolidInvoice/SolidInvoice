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

use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Entity\LineTax;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ExactlyOneLineValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ExactlyOneLine) {
            throw new UnexpectedTypeException($constraint, ExactlyOneLine::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof LineTax) {
            throw new UnexpectedValueException($value, LineTax::class);
        }

        $invoiceLine = $value->getInvoiceLine();
        $quoteLine = $value->getQuoteLine();

        $hasInvoice = $invoiceLine instanceof Line;
        $hasQuote = $quoteLine instanceof \SolidInvoice\QuoteBundle\Entity\Line;

        if ($hasInvoice === $hasQuote) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
