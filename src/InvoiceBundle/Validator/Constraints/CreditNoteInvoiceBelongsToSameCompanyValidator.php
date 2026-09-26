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

namespace SolidInvoice\InvoiceBundle\Validator\Constraints;

use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Validator\Constraints\CreditNoteInvoiceBelongsToSameCompanyValidatorTest
 */
final class CreditNoteInvoiceBelongsToSameCompanyValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof CreditNoteInvoiceBelongsToSameCompany) {
            throw new UnexpectedTypeException($constraint, CreditNoteInvoiceBelongsToSameCompany::class);
        }

        if (! $value instanceof CreditNote) {
            throw new UnexpectedValueException($value, CreditNote::class);
        }

        $invoice = $value->getInvoice();

        if (! $invoice instanceof Invoice) {
            return;
        }

        if ($value->getCompany()->getId()->toRfc4122() !== $invoice->getCompany()->getId()->toRfc4122()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('invoice')
                ->addViolation();
        }
    }
}
