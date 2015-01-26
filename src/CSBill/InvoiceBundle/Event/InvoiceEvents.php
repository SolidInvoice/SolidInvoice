<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Event;

final class InvoiceEvents
{
    const INVOICE_PRE_PAID = 'invoice.pre_paid';
    const INVOICE_POST_PAID = 'invoice.post_paid';

    const INVOICE_PRE_ACCEPT = 'invoice.pre_accept';
    const INVOICE_POST_ACCEPT = 'invoice.post_accept';

    const INVOICE_PRE_CANCEL = 'invoice.pre_cancel';
    const INVOICE_POST_CANCEL = 'invoice.post_cancel';

    const INVOICE_PRE_CREATE = 'invoice.pre_create';
    const INVOICE_POST_CREATE = 'invoice.post_create';
}