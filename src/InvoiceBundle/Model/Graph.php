<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Model;

final class Graph
{
    const GRAPH = 'csbill_invoice';

    const TRANSITION_ACCEPT = 'accept';
    const TRANSITION_NEW = 'new';
    const TRANSITION_CANCEL = 'cancel';
    const TRANSITION_OVERDUE = 'overdue';
    const TRANSITION_PAY = 'pay';
    const TRANSITION_REOPEN = 'reopen';
    const TRANSITION_ARCHIVE = 'archive';
    const TRANSITION_RECURRING = 'recurring';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_NEW = 'new';
    const STATUS_RECURRING = 'recurring';
}
