'use strict';

const { quoteOutputFields, quoteStatusField } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  if (bundle.inputData.id) {
    const response = await z.request({
      url: `${bundle.authData.server_url}/api/quotes/${bundle.inputData.id}`,
      method: 'GET',
    });
    return response.data ? [response.data] : [];
  }

  const params = {};
  if (bundle.inputData.quoteId) params.quoteId = bundle.inputData.quoteId;
  if (bundle.inputData.status) params.status = bundle.inputData.status;
  if (bundle.inputData.client) {
    params.client = bundle.inputData.client.startsWith('/api/clients/')
      ? bundle.inputData.client
      : `/api/clients/${bundle.inputData.client}`;
  }

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/quotes`,
    method: 'GET',
    params,
  });
  return (response.data && response.data['hydra:member']) || [];
};

module.exports = {
  key: 'find_quote',
  noun: 'Quote',
  display: {
    label: 'Find Quote',
    description: 'Find a quote by ULID, quote number, status, or client.',
  },
  operation: {
    perform,
    inputFields: [
      {
        key: 'id',
        label: 'Quote',
        type: 'string',
        required: false,
        dynamic: 'new_quote.id.quoteId',
        helpText: 'Pick a quote, or leave empty to filter by the fields below.',
      },
      {
        key: 'quoteId',
        label: 'Quote Number',
        type: 'string',
        required: false,
        dynamic: 'new_quote.quoteId.quoteId',
      },
      quoteStatusField(false),
      {
        key: 'client',
        label: 'Client',
        type: 'string',
        required: false,
        dynamic: 'new_client.id.name',
      },
    ],
    sample: samples.quote,
    outputFields: quoteOutputFields,
  },
};
