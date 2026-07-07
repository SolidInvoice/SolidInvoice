'use strict';

const { clientDropdown, quoteOutputFields } = require('../utils/fields');
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
    url: `${bundle.authData.server_url}/api/quotes`,
    method: 'POST',
    body,
  });
  return response.data;
};

module.exports = {
  key: 'create_quote',
  noun: 'Quote',
  display: {
    label: 'Create Quote',
    description: 'Create a new quote in draft status.',
  },
  operation: {
    perform,
    inputFields: [
      clientDropdown(true),
      { key: 'due', label: 'Valid Until', type: 'datetime', required: false },
      {
        key: 'users',
        label: 'Recipient Contact IRIs',
        type: 'string',
        list: true,
        required: false,
      },
      {
        key: 'lines',
        label: 'Quote Lines',
        required: true,
        children: [
          { key: 'description', label: 'Description', type: 'string', required: true },
          { key: 'price', label: 'Unit Price', type: 'number', required: true },
          { key: 'qty', label: 'Quantity', type: 'number', required: true, default: '1' },
        ],
      },
      { key: 'terms', label: 'Terms', type: 'text', required: false },
      { key: 'notes', label: 'Notes', type: 'text', required: false },
    ],
    sample: samples.quote,
    outputFields: quoteOutputFields,
  },
};
