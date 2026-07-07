'use strict';

const { choices } = require('../constants');
const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const response = await z.request({
    url: `${bundle.authData.server_url}/api/invoices/${bundle.inputData.invoice}/transitions/${bundle.inputData.transition}`,
    method: 'POST',
    body: {},
  });
  return response.data;
};

module.exports = {
  key: 'transition_invoice',
  noun: 'Invoice',
  display: {
    label: 'Transition Invoice',
    description:
      'Move an invoice to a new state (accept, pay, cancel, overdue, reopen, archive, edit). The API rejects invalid transitions for the current status.',
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
        key: 'transition',
        label: 'Transition',
        type: 'string',
        required: true,
        choices: choices.invoiceTransition,
      },
    ],
    sample: samples.invoice,
    outputFields: invoiceOutputFields,
  },
};
