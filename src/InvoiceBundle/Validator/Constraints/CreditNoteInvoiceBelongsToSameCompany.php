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
 * `CompanyFilter` scopes the credit note itself, but nothing intrinsically stops a payload from
 * naming another company's invoice ULID on `invoice_id`. See `design` §9 on SOL-69.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Validator\Constraints\CreditNoteInvoiceBelongsToSameCompanyValidatorTest
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class CreditNoteInvoiceBelongsToSameCompany extends Constraint
{
    public string $message = 'invoice.constraint.credit_note_invoice_same_company';

    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
