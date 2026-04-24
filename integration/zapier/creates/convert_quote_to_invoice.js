'use strict';

const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const response = await z.request({
    url: `${bundle.authData.server_url}/api/quotes/${bundle.inputData.quote}/invoice`,
    method: 'POST',
    body: {},
  });
  return response.data;
};

module.exports = {
  key: 'convert_quote_to_invoice',
  noun: 'Invoice',
  display: {
    label: 'Convert Quote to Invoice',
    description:
      'Convert an accepted quote into a draft invoice. Returns the new invoice.',
  },
  operation: {
    perform,
    inputFields: [
      {
        key: 'quote',
        label: 'Quote',
        type: 'string',
        required: true,
        dynamic: 'new_quote.id.quoteId',
        search: 'find_quote.id',
      },
    ],
    sample: samples.invoice,
    outputFields: invoiceOutputFields,
  },
};
