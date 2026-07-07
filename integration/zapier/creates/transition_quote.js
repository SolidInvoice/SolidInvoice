'use strict';

const { choices } = require('../constants');
const { quoteOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const response = await z.request({
    url: `${bundle.authData.server_url}/api/quotes/${bundle.inputData.quote}/transitions/${bundle.inputData.transition}`,
    method: 'POST',
    body: {},
  });
  return response.data;
};

module.exports = {
  key: 'transition_quote',
  noun: 'Quote',
  display: {
    label: 'Transition Quote',
    description:
      'Move a quote to a new state (send, accept, decline, cancel, reopen, archive).',
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
      {
        key: 'transition',
        label: 'Transition',
        type: 'string',
        required: true,
        choices: choices.quoteTransition,
      },
    ],
    sample: samples.quote,
    outputFields: quoteOutputFields,
  },
};
