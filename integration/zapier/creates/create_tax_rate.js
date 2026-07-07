'use strict';

const { TAX_TYPES } = require('../constants');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const body = {
    name: bundle.inputData.name,
    rate: parseFloat(bundle.inputData.rate),
    type: bundle.inputData.type,
  };

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/taxes`,
    method: 'POST',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'create_tax_rate',
  noun: 'Tax Rate',
  display: {
    label: 'Create Tax Rate',
    description: 'Create a new tax rate.',
  },
  operation: {
    perform,
    inputFields: [
      { key: 'name', label: 'Name', type: 'string', required: true },
      {
        key: 'rate',
        label: 'Rate (%)',
        type: 'number',
        required: true,
        helpText: 'The tax rate as a percentage, e.g. 20 for 20% VAT.',
      },
      {
        key: 'type',
        label: 'Type',
        type: 'string',
        required: true,
        choices: TAX_TYPES.reduce((acc, t) => {
          acc[t] = t;
          return acc;
        }, {}),
      },
    ],
    sample: samples.tax,
    outputFields: [
      { key: 'id', label: 'ID' },
      { key: 'name', label: 'Name' },
      { key: 'rate', label: 'Rate', type: 'number' },
      { key: 'type', label: 'Type' },
    ],
  },
};
