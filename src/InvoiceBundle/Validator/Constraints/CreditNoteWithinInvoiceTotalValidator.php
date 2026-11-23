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
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Validator\Constraints\CreditNoteWithinInvoiceTotalValidatorTest
 */
final class CreditNoteWithinInvoiceTotalValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CreditNoteRepository $creditNoteRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof CreditNoteWithinInvoiceTotal) {
            throw new UnexpectedTypeException($constraint, CreditNoteWithinInvoiceTotal::class);
        }

        if (! $value instanceof CreditNote) {
            throw new UnexpectedValueException($value, CreditNote::class);
        }

        // A cancelled credit note never counts against the cap, including when it is the one
        // being (re-)validated.
        if (CreditNoteStatus::Cancelled === $value->getStatus()) {
            return;
        }

        $invoice = $value->getInvoice();

        if (! $invoice instanceof Invoice) {
            return;
        }

        $creditedByOthers = $this->creditNoteRepository->getTotalCreditedForInvoice($invoice, $value);
        $projectedTotal = $creditedByOthers->toBigDecimal()->plus($value->getTotal());

        if ($projectedTotal->isGreaterThan($invoice->getTotal())) {
            $this->context->buildViolation($constraint->message)
                ->atPath('total')
                ->addViolation();
        }
    }
}
