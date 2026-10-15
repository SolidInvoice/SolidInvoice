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

namespace SolidInvoice\CoreBundle\Billing;

/**
 * Which of the three billing document tables a row belongs to.
 *
 * Lives here rather than under `Billing/Audit/` because this enum is used by
 * {@see \SolidInvoice\CoreBundle\Entity\AmbiguousDiscountUnit}, which merges before that
 * namespace exists. Backed by `string(20)`, matching `ambiguous_discount_units.document_type`.
 */
enum BillingDocumentKind: string
{
    case Invoice = 'invoice';
    case Quote = 'quote';
    case RecurringInvoice = 'recurring_invoice';
}
