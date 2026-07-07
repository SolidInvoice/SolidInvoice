'use strict';

const { fetchCollection } = require('../utils/pagination');
const samples = require('../utils/samples');

const perform = async (z, bundle) =>
  fetchCollection(z, bundle, '/api/taxes', {});

module.exports = {
  key: 'new_tax_rate',
  noun: 'Tax Rate',
  display: {
    label: 'New Tax Rate',
    description: 'Triggers when a new tax rate is configured.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.tax,
    outputFields: [
      { key: 'id', label: 'ID' },
      { key: 'name', label: 'Name' },
      { key: 'rate', label: 'Rate', type: 'number' },
      { key: 'type', label: 'Type' },
    ],
  },
};
