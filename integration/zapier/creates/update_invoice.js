'use strict';

const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const body = {};
  ['terms', 'notes', 'invoiceDate', 'due'].forEach((k) => {
    if (bundle.inputData[k] !== undefined && bundle.inputData[k] !== '') {
      body[k] = bundle.inputData[k];
    }
  });

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/invoices/${bundle.inputData.invoice}`,
    method: 'PATCH',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'update_invoice',
  noun: 'Invoice',
  display: {
    label: 'Update Invoice',
    description: 'Update mutable fields on an existing invoice. Status changes use Transition Invoice.',
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
      { key: 'invoiceDate', label: 'Invoice Date', type: 'datetime', required: false },
      { key: 'due', label: 'Due Date', type: 'datetime', required: false },
      { key: 'terms', label: 'Terms', type: 'text', required: false },
      { key: 'notes', label: 'Notes', type: 'text', required: false },
    ],
    sample: samples.invoice,
    outputFields: invoiceOutputFields,
  },
};
