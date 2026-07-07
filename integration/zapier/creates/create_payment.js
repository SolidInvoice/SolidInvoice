'use strict';

const { paymentOutputFields } = require('../utils/fields');
const { toCents } = require('../utils/money');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const body = {
    totalAmount: toCents(bundle.inputData.amount),
    currencyCode: bundle.inputData.currencyCode || null,
    method: bundle.inputData.method ? { name: bundle.inputData.method } : null,
    reference: bundle.inputData.reference || null,
    notes: bundle.inputData.notes || null,
    status: bundle.inputData.status || 'captured',
  };

  if (bundle.inputData.completed) {
    body.completed = bundle.inputData.completed;
  }

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/invoices/${bundle.inputData.invoice}/payments`,
    method: 'POST',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'create_payment',
  noun: 'Payment',
  display: {
    label: 'Record Payment',
    description: 'Record a payment against an invoice.',
  },
  operation: {
    perform,
    inputFields: [
      {
        key: 'invoice',
        label: 'Invoice',
        type: 'string',
        required: true,
        dynamic: 'new_invoice.id.invoiceId',
        search: 'find_invoice.id',
      },
      {
        key: 'amount',
        label: 'Amount',
        type: 'number',
        required: true,
        helpText: 'Decimal amount, e.g. 100.50. Converted to cents automatically.',
      },
      {
        key: 'currencyCode',
        label: 'Currency',
        type: 'string',
        required: false,
        placeholder: 'USD',
      },
      {
        key: 'method',
        label: 'Payment Method',
        type: 'string',
        required: false,
        helpText: 'Free-text method name, e.g. "Bank Transfer", "Cash", "Stripe".',
      },
      { key: 'reference', label: 'Reference', type: 'string', required: false },
      { key: 'notes', label: 'Notes', type: 'text', required: false },
      {
        key: 'status',
        label: 'Status',
        type: 'string',
        required: false,
        default: 'captured',
        choices: { captured: 'Captured', pending: 'Pending', refunded: 'Refunded' },
      },
      { key: 'completed', label: 'Completed At', type: 'datetime', required: false },
    ],
    sample: samples.payment,
    outputFields: paymentOutputFields,
  },
};
