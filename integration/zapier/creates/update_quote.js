'use strict';

const { quoteOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const body = {};
  ['terms', 'notes', 'due'].forEach((k) => {
    if (bundle.inputData[k] !== undefined && bundle.inputData[k] !== '') {
      body[k] = bundle.inputData[k];
    }
  });

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/quotes/${bundle.inputData.quote}`,
    method: 'PATCH',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'update_quote',
  noun: 'Quote',
  display: {
    label: 'Update Quote',
    description: 'Update mutable fields on an existing quote.',
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
      { key: 'due', label: 'Valid Until', type: 'datetime', required: false },
      { key: 'terms', label: 'Terms', type: 'text', required: false },
      { key: 'notes', label: 'Notes', type: 'text', required: false },
    ],
    sample: samples.quote,
    outputFields: quoteOutputFields,
  },
};
