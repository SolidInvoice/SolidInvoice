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

namespace SolidInvoice\PaymentBundle\Enum;

/**
 * Why a payment cannot be recorded or voided in the invoice's or payment's current state. The value
 * is the anchor of the problem's description in the REST API documentation.
 */
enum PaymentConflict: string
{
    case InvoiceNotIssued = 'invoice_not_issued';
    case InvoiceNotPayable = 'invoice_not_payable';
    case PaymentNotVoidable = 'payment_not_voidable';

    private const string DOCS_URL = 'https://solidinvoice.co/docs/api/rest-api';

    public function type(): string
    {
        return self::DOCS_URL . '#' . $this->value;
    }
}
