'use strict';

const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const response = await z.request({
    url: `${bundle.authData.server_url}/api/invoices/${bundle.inputData.invoice}/transitions/accept`,
    method: 'POST',
    body: {},
  });
  return response.data;
};

module.exports = {
  key: 'send_invoice',
  noun: 'Invoice',
  display: {
    label: 'Send Invoice',
    description:
      'Transition a draft invoice to pending, which queues it for delivery to the client contacts.',
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
        helpText: 'The invoice to finalize and send. Must currently be in draft or new status.',
      },
    ],
    sample: { ...samples.invoice, status: 'pending' },
    outputFields: invoiceOutputFields,
  },
};
