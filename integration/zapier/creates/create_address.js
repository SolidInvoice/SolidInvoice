'use strict';

const { clientDropdown } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const clientId = bundle.inputData.client.startsWith('/api/clients/')
    ? bundle.inputData.client.split('/').pop()
    : bundle.inputData.client;

  const body = {
    street1: bundle.inputData.street1 || null,
    street2: bundle.inputData.street2 || null,
    city: bundle.inputData.city || null,
    state: bundle.inputData.state || null,
    zip: bundle.inputData.zip || null,
    country: bundle.inputData.country || null,
  };

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/clients/${clientId}/addresses`,
    method: 'POST',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'create_address',
  noun: 'Address',
  display: {
    label: 'Create Address',
    description: 'Add an address to an existing client.',
  },
  operation: {
    perform,
    inputFields: [
      clientDropdown(true),
      { key: 'street1', label: 'Street 1', type: 'string', required: false },
      { key: 'street2', label: 'Street 2', type: 'string', required: false },
      { key: 'city', label: 'City', type: 'string', required: false },
      { key: 'state', label: 'State/Region', type: 'string', required: false },
      { key: 'zip', label: 'Postal Code', type: 'string', required: false },
      {
        key: 'country',
        label: 'Country',
        type: 'string',
        required: false,
        helpText: 'Two-letter ISO country code (e.g. US, GB).',
      },
    ],
    sample: samples.address,
    outputFields: [
      { key: 'id', label: 'ID' },
      { key: 'street1', label: 'Street 1' },
      { key: 'street2', label: 'Street 2' },
      { key: 'city', label: 'City' },
      { key: 'state', label: 'State/Region' },
      { key: 'zip', label: 'Postal Code' },
      { key: 'country', label: 'Country' },
    ],
  },
};
