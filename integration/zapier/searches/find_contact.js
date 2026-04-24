'use strict';

const { clientDropdown } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const clientId = bundle.inputData.client.startsWith('/api/clients/')
    ? bundle.inputData.client.split('/').pop()
    : bundle.inputData.client;

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/clients/${clientId}/contacts`,
    method: 'GET',
  });
  const members = (response.data && response.data['hydra:member']) || [];
  if (bundle.inputData.email) {
    return members.filter(
      (m) => m.email && m.email.toLowerCase() === bundle.inputData.email.toLowerCase()
    );
  }
  return members;
};

module.exports = {
  key: 'find_contact',
  noun: 'Contact',
  display: {
    label: 'Find Contact',
    description: 'Find a contact on a client by email address.',
  },
  operation: {
    perform,
    inputFields: [
      clientDropdown(true),
      { key: 'email', label: 'Email', type: 'string', required: false },
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
