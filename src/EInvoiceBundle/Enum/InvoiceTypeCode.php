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

namespace SolidInvoice\EInvoiceBundle\Enum;

/**
 * UNTDID 1001 document name codes, as EN 16931 restricts them for BT-3.
 *
 * Pulled forward from SOL-82 (SolidInvoice#2658) so the semantic model in `Model/` has a stable
 * type to carry BT-3 against. SOL-82 still owns the `Invoice` column, its default, the migration
 * and the API exposure of this code list; this enum is a value type only.
 */
enum InvoiceTypeCode: string
{
    case CommercialInvoice = '380';
    case CreditNote = '381';
    case CorrectedInvoice = '384';
    case PrepaymentInvoice = '386';
    case SelfBilledInvoice = '389';

    public function getLabel(): string
    {
        return match ($this) {
            self::CommercialInvoice => 'Commercial invoice',
            self::CreditNote => 'Credit note',
            self::CorrectedInvoice => 'Corrected invoice',
            self::PrepaymentInvoice => 'Prepayment invoice',
            self::SelfBilledInvoice => 'Self-billed invoice',
        };
    }
}
