'use strict';

const { clientDropdown } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const clientId = bundle.inputData.client.startsWith('/api/clients/')
    ? bundle.inputData.client.split('/').pop()
    : bundle.inputData.client;

  const page = (bundle.meta && bundle.meta.page ? bundle.meta.page : 0) + 1;
  const response = await z.request({
    url: `${bundle.authData.server_url}/api/clients/${clientId}/contacts`,
    method: 'GET',
    params: { page, itemsPerPage: 30 },
  });
  return (response.data && response.data['hydra:member']) || [];
};

module.exports = {
  key: 'new_contact',
  noun: 'Contact',
  display: {
    label: 'New Contact',
    description: 'Triggers when a new contact is added to a client.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    inputFields: [clientDropdown(true)],
    sample: samples.contact,
    outputFields: [
      { key: 'id', label: 'ID' },
      { key: 'firstName', label: 'First Name' },
      { key: 'lastName', label: 'Last Name' },
      { key: 'email', label: 'Email' },
      { key: 'type', label: 'Type' },
    ],
  },
};
