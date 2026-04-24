'use strict';

const { clientOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const body = {
    name: bundle.inputData.name,
    website: bundle.inputData.website || null,
    currencyCode: bundle.inputData.currencyCode || null,
    vatNumber: bundle.inputData.vatNumber || null,
    contacts: [
      {
        firstName: bundle.inputData.contact_firstName,
        lastName: bundle.inputData.contact_lastName || null,
        email: bundle.inputData.contact_email,
      },
    ],
  };

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/clients`,
    method: 'POST',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'create_client',
  noun: 'Client',
  display: {
    label: 'Create Client',
    description: 'Create a new client with a primary contact.',
  },
  operation: {
    perform,
    inputFields: [
      { key: 'name', label: 'Client Name', type: 'string', required: true },
      { key: 'website', label: 'Website', type: 'string', required: false },
      {
        key: 'currencyCode',
        label: 'Currency',
        type: 'string',
        required: false,
        placeholder: 'USD',
        helpText: 'Three-letter ISO currency code. Leave empty to use the company default.',
      },
      { key: 'vatNumber', label: 'VAT Number', type: 'string', required: false },
      {
        key: 'contact_firstName',
        label: 'Contact First Name',
        type: 'string',
        required: true,
      },
      {
        key: 'contact_lastName',
        label: 'Contact Last Name',
        type: 'string',
        required: false,
      },
      {
        key: 'contact_email',
        label: 'Contact Email',
        type: 'string',
        required: true,
        helpText: 'Primary contact email — invoices and quotes are delivered here by default.',
      },
    ],
    sample: samples.client,
    outputFields: clientOutputFields,
  },
};
