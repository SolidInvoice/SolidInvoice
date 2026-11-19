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

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

/**
 * The over-crediting cap: the sum of non-cancelled credit notes against an invoice may not
 * exceed that invoice's total. Enforced as a validator rather than a silent clamp, so an
 * over-crediting attempt is rejected rather than quietly truncated. See `design` §0 on SOL-69.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Validator\Constraints\CreditNoteWithinInvoiceTotalValidatorTest
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class CreditNoteWithinInvoiceTotal extends Constraint
{
    public string $message = 'invoice.constraint.credit_note_exceeds_invoice_total';

    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
