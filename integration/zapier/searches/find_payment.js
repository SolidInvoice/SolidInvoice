'use strict';

const { paymentOutputFields } = require('../utils/fields');
const { choices } = require('../constants');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  if (bundle.inputData.id) {
    const response = await z.request({
      url: `${bundle.authData.server_url}/api/payments/${bundle.inputData.id}`,
      method: 'GET',
    });
    return response.data ? [response.data] : [];
  }

  const params = {};
  if (bundle.inputData.status) params.status = bundle.inputData.status;
  if (bundle.inputData.reference) params.reference = bundle.inputData.reference;
  if (bundle.inputData.invoice) {
    params.invoice = bundle.inputData.invoice.startsWith('/api/invoices/')
      ? bundle.inputData.invoice
      : `/api/invoices/${bundle.inputData.invoice}`;
  }

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/payments`,
    method: 'GET',
    params,
  });
  return (response.data && response.data['hydra:member']) || [];
};

module.exports = {
  key: 'find_payment',
  noun: 'Payment',
  display: {
    label: 'Find Payment',
    description: 'Find a payment by ULID, status, reference, or invoice.',
  },
  operation: {
    perform,
    inputFields: [
      {
        key: 'id',
        label: 'Payment',
        type: 'string',
        required: false,
        dynamic: 'new_payment.id.reference',
        helpText: 'Pick a payment, or leave empty to filter by the fields below.',
      },
      { key: 'reference', label: 'Reference', type: 'string', required: false },
      {
        key: 'status',
        label: 'Status',
        type: 'string',
        required: false,
        choices: choices.paymentStatus,
      },
      {
        key: 'invoice',
        label: 'Invoice',
        type: 'string',
        required: false,
        dynamic: 'new_invoice.id.invoiceId',
      },
    ],
    sample: samples.payment,
    outputFields: paymentOutputFields,
  },
};
