'use strict';

const { invoiceOutputFields, invoiceStatusField } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  if (bundle.inputData.id) {
    const response = await z.request({
      url: `${bundle.authData.server_url}/api/invoices/${bundle.inputData.id}`,
      method: 'GET',
    });
    return response.data ? [response.data] : [];
  }

  const params = {};
  if (bundle.inputData.invoiceId) params.invoiceId = bundle.inputData.invoiceId;
  if (bundle.inputData.status) params.status = bundle.inputData.status;
  if (bundle.inputData.client) {
    params.client = bundle.inputData.client.startsWith('/api/clients/')
      ? bundle.inputData.client
      : `/api/clients/${bundle.inputData.client}`;
  }

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/invoices`,
    method: 'GET',
    params,
  });
  return (response.data && response.data['hydra:member']) || [];
};

module.exports = {
  key: 'find_invoice',
  noun: 'Invoice',
  display: {
    label: 'Find Invoice',
    description: 'Find an invoice by ULID, invoice number, status, or client.',
  },
  operation: {
    perform,
    inputFields: [
      {
        key: 'id',
        label: 'Invoice',
        type: 'string',
        required: false,
        dynamic: 'new_invoice.id.invoiceId',
        helpText: 'Pick an invoice, or leave empty to filter by the fields below.',
      },
      {
        key: 'invoiceId',
        label: 'Invoice Number',
        type: 'string',
        required: false,
        dynamic: 'new_invoice.invoiceId.invoiceId',
      },
      invoiceStatusField(false),
      {
        key: 'client',
        label: 'Client',
        type: 'string',
        required: false,
        dynamic: 'new_client.id.name',
      },
    ],
    sample: samples.invoice,
    outputFields: invoiceOutputFields,
  },
};
