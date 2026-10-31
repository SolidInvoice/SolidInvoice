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
 * Transition names for the `einvoice_document` workflow declared in
 * `config/packages/workflow.php` (SOL-208). Backed so the workflow configuration and any caller
 * reference the same names as a compile-time-checked value, never a string literal.
 */
enum EInvoiceTransition: string
{
    case Queue = 'queue';
    case Transmit = 'transmit';
    case Accept = 'accept';
    case Reject = 'reject';
    case Cancel = 'cancel';
    case Fail = 'fail';
}
