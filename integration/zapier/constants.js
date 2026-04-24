'use strict';

const DEFAULT_INSTANCE_URL = 'https://solidinvoice.app';

const INVOICE_STATUSES = [
  'new',
  'draft',
  'pending',
  'active',
  'overdue',
  'cancelled',
  'archived',
  'paid',
];

const QUOTE_STATUSES = [
  'new',
  'draft',
  'pending',
  'accepted',
  'declined',
  'cancelled',
  'archived',
];

const RECURRING_INVOICE_STATUSES = [
  'new',
  'draft',
  'active',
  'paused',
  'complete',
  'cancelled',
  'archived',
];

const PAYMENT_STATUSES = [
  'unknown',
  'failed',
  'suspended',
  'expired',
  'pending',
  'cancelled',
  'new',
  'captured',
  'authorized',
  'refunded',
  'credit',
];

const CLIENT_STATUSES = ['active', 'inactive', 'archived'];

const INVOICE_TRANSITIONS = [
  'new',
  'accept',
  'cancel',
  'overdue',
  'pay',
  'reopen',
  'archive',
  'edit',
];

const QUOTE_TRANSITIONS = [
  'new',
  'send',
  'publish',
  'cancel',
  'decline',
  'accept',
  'reopen',
  'archive',
];

const RECURRING_INVOICE_TRANSITIONS = [
  'new',
  'activate',
  'cancel',
  'complete',
  'pause',
  'resume',
  'archive',
  'edit',
];

const TAX_TYPES = ['Inclusive', 'Exclusive'];

const DEFAULT_PAGE_SIZE = 30;

const toChoiceObject = (values) =>
  values.reduce((acc, v) => {
    acc[v] = v.charAt(0).toUpperCase() + v.slice(1);
    return acc;
  }, {});

module.exports = {
  DEFAULT_INSTANCE_URL,
  DEFAULT_PAGE_SIZE,
  INVOICE_STATUSES,
  QUOTE_STATUSES,
  RECURRING_INVOICE_STATUSES,
  PAYMENT_STATUSES,
  CLIENT_STATUSES,
  INVOICE_TRANSITIONS,
  QUOTE_TRANSITIONS,
  RECURRING_INVOICE_TRANSITIONS,
  TAX_TYPES,
  choices: {
    invoiceStatus: toChoiceObject(INVOICE_STATUSES),
    quoteStatus: toChoiceObject(QUOTE_STATUSES),
    recurringInvoiceStatus: toChoiceObject(RECURRING_INVOICE_STATUSES),
    paymentStatus: toChoiceObject(PAYMENT_STATUSES),
    clientStatus: toChoiceObject(CLIENT_STATUSES),
    invoiceTransition: toChoiceObject(INVOICE_TRANSITIONS),
    quoteTransition: toChoiceObject(QUOTE_TRANSITIONS),
    recurringInvoiceTransition: toChoiceObject(RECURRING_INVOICE_TRANSITIONS),
  },
};
