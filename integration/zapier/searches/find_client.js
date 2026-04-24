'use strict';

const { clientOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const params = {};
  if (bundle.inputData.name) params.name = bundle.inputData.name;
  if (bundle.inputData.status) params.status = bundle.inputData.status;

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/clients`,
    method: 'GET',
    params,
  });
  const members = (response.data && response.data['hydra:member']) || [];
  return members;
};

module.exports = {
  key: 'find_client',
  noun: 'Client',
  display: {
    label: 'Find Client',
    description: 'Find a client by name or status.',
  },
  operation: {
    perform,
    inputFields: [
      { key: 'name', label: 'Name (partial match)', type: 'string', required: false },
      {
        key: 'status',
        label: 'Status',
        type: 'string',
        required: false,
        choices: { active: 'Active', inactive: 'Inactive', archived: 'Archived' },
      },
    ],
    sample: samples.client,
    outputFields: clientOutputFields,
  },
};
