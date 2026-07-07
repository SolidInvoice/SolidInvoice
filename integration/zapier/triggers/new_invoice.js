'use strict';

const { fetchCollection } = require('../utils/pagination');
const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) =>
  fetchCollection(z, bundle, '/api/invoices', {
    'order[createdAt]': 'desc',
  });

module.exports = {
  key: 'new_invoice',
  noun: 'Invoice',
  display: {
    label: 'New Invoice',
    description: 'Triggers when a new invoice is created.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.invoice,
    outputFields: invoiceOutputFields,
  },
};
