<?php

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

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
}