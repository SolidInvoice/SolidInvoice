'use strict';

const { clientDropdown } = require('../utils/fields');
const { invoiceOutputFields } = require('../utils/fields');
const { toCents } = require('../utils/money');
const samples = require('../utils/samples');

const resolveClientIri = (input) =>
  input.startsWith('/api/clients/') ? input : `/api/clients/${input}`;

const perform = async (z, bundle) => {
  const lines = (bundle.inputData.lines || []).map((line) => ({
    description: line.description,
    price: toCents(line.price),
    qty: line.qty,
  }));

  const body = {
    client: resolveClientIri(bundle.inputData.client),
    invoiceDate: bundle.inputData.invoiceDate || new Date().toISOString().slice(0, 10),
    due: bundle.inputData.due || null,
    terms: bundle.inputData.terms || null,
    notes: bundle.inputData.notes || null,
    lines,
  };

  if (bundle.inputData.users) {
    body.users = Array.isArray(bundle.inputData.users)
      ? bundle.inputData.users
      : [bundle.inputData.users];
  }

  const response = await z.request({
    url: `${bundle.authData.server_url}/api/invoices`,
    method: 'POST',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'create_invoice',
  noun: 'Invoice',
  display: {
    label: 'Create Invoice',
    description: 'Create a new invoice in draft status. Send it separately with the Send Invoice action.',
  },
  operation: {
    perform,
    inputFields: [
      clientDropdown(true),
      {
        key: 'invoiceDate',
        label: 'Invoice Date',
        type: 'datetime',
        required: false,
        helpText: 'Defaults to today if omitted.',
      },
      { key: 'due', label: 'Due Date', type: 'datetime', required: false },
      {
        key: 'users',
        label: 'Recipient Contact IRIs',
        type: 'string',
        list: true,
        required: false,
        helpText: 'Contact IRIs (e.g. `/api/clients/{id}/contact/{id}`). Leave empty to use the client primary contact.',
      },
      {
        key: 'lines',
        label: 'Invoice Lines',
        required: true,
        children: [
          { key: 'description', label: 'Description', type: 'string', required: true },
          {
            key: 'price',
            label: 'Unit Price',
            type: 'number',
            required: true,
            helpText: 'Decimal amount, e.g. 100.50. Converted to cents automatically.',
          },
          { key: 'qty', label: 'Quantity', type: 'number', required: true, default: '1' },
        ],
      },
      { key: 'terms', label: 'Terms', type: 'text', required: false },
      { key: 'notes', label: 'Notes', type: 'text', required: false },
    ],
    sample: samples.invoice,
    outputFields: invoiceOutputFields,
  },
};
