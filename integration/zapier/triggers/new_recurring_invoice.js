'use strict';

const { fetchCollection } = require('../utils/pagination');
const samples = require('../utils/samples');

const perform = async (z, bundle) =>
  fetchCollection(z, bundle, '/api/recurring-invoices', {
    'order[createdAt]': 'desc',
  });

module.exports = {
  key: 'new_recurring_invoice',
  noun: 'Recurring Invoice',
  display: {
    label: 'New Recurring Invoice',
    description: 'Triggers when a new recurring invoice is set up.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.recurringInvoice,
    outputFields: [
      { key: 'id', label: 'ID' },
      { key: 'status', label: 'Status' },
      { key: 'frequency', label: 'Frequency' },
      { key: 'client', label: 'Client IRI' },
      { key: 'total', label: 'Total (cents)', type: 'integer' },
    ],
  },
};
