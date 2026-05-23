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

namespace SolidInvoice\InvoiceBundle\Event;

final class InvoiceEvents
{
    public const string INVOICE_PRE_PAID = 'invoice.pre_paid';

    public const string INVOICE_POST_PAID = 'invoice.post_paid';

    public const string INVOICE_PRE_ACCEPT = 'invoice.pre_accept';

    public const string INVOICE_POST_ACCEPT = 'invoice.post_accept';

    public const string INVOICE_PRE_CANCEL = 'invoice.pre_cancel';

    public const string INVOICE_POST_CANCEL = 'invoice.post_cancel';

    public const string INVOICE_PRE_CREATE = 'invoice.pre_create';

    public const string INVOICE_POST_CREATE = 'invoice.post_create';

    public const string INVOICE_PRE_REOPEN = 'invoice.pre_reopen';

    public const string INVOICE_POST_REOPEN = 'invoice.post_reopen';

    public const string INVOICE_PRE_ARCHIVE = 'invoice.pre_archive';

    public const string INVOICE_POST_ARCHIVE = 'invoice.post_archive';
}
