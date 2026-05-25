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

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ExactlyOneDocumentValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ExactlyOneDocument) {
            throw new UnexpectedTypeException($constraint, ExactlyOneDocument::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof InvoiceTax) {
            throw new UnexpectedValueException($value, InvoiceTax::class);
        }

        $owners = [
            $value->getInvoice() instanceof Invoice,
            $value->getQuote() instanceof Quote,
            $value->getRecurringInvoice() instanceof RecurringInvoice,
        ];

        $ownerCount = count(array_filter($owners));

        // Skip new (unpersisted) entries with no parent yet — the form-manager
        // (InvoiceFormManager / QuoteFormManager) wires the back-reference
        // after form validation runs, so an in-flight InvoiceTax legitimately
        // has no side set during binding.
        if (! $value->getId() instanceof Ulid && $ownerCount === 0) {
            return;
        }

        if ($ownerCount !== 1) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
