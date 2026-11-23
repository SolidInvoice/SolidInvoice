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

namespace SolidInvoice\EInvoiceBundle\Validator\Constraints;

use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use function array_filter;
use function count;

/**
 * Unlike {@see \SolidInvoice\TaxBundle\Validator\Constraints\ExactlyOneDocumentValidator}, this
 * validator has no escape hatch for an unpersisted row with no owner set. That escape hatch
 * exists there because `InvoiceFormManager` wires the back-reference after form validation runs.
 * `EInvoiceDocument` has no form — its source is a constructor argument — so it has no such
 * window: a count other than 1 is always a violation, including for an unpersisted row.
 * `design` §3.3 on SOL-89.
 *
 * @see \SolidInvoice\EInvoiceBundle\Tests\Validator\Constraints\ExactlyOneSourceDocumentValidatorTest
 */
final class ExactlyOneSourceDocumentValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ExactlyOneSourceDocument) {
            throw new UnexpectedTypeException($constraint, ExactlyOneSourceDocument::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof EInvoiceDocument) {
            throw new UnexpectedValueException($value, EInvoiceDocument::class);
        }

        $owners = [
            $value->getInvoice() instanceof Invoice,
            $value->getCreditNote() instanceof CreditNote,
        ];

        if (count(array_filter($owners)) !== 1) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
