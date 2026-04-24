'use strict';

const { clientDropdown } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const clientId = bundle.inputData.client.startsWith('/api/clients/')
    ? bundle.inputData.client.split('/').pop()
    : bundle.inputData.client;

  const body = {
    firstName: bundle.inputData.firstName,
    lastName: bundle.inputData.lastName || null,
    email: bundle.inputData.email,
  };

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/clients/${clientId}/contacts`,
    method: 'POST',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'create_contact',
  noun: 'Contact',
  display: {
    label: 'Create Contact',
    description: 'Add a contact to an existing client.',
  },
  operation: {
    perform,
    inputFields: [
      clientDropdown(true),
      { key: 'firstName', label: 'First Name', type: 'string', required: true },
      { key: 'lastName', label: 'Last Name', type: 'string', required: false },
      { key: 'email', label: 'Email', type: 'string', required: true },
    ],
    sample: samples.contact,
    outputFields: [
      { key: 'id', label: 'ID' },
      { key: 'firstName', label: 'First Name' },
      { key: 'lastName', label: 'Last Name' },
      { key: 'email', label: 'Email' },
    ],
  },
};
