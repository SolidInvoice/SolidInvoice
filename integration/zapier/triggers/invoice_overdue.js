'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const items = await fetchCollection(z, bundle, '/api/invoices', {
    status: 'overdue',
    'order[due]': 'desc',
  });
  return items.map((i) => ({
    ...i,
    id: makeSyntheticId(i, 'overdue', i.due || i.updatedAt),
    invoiceRecordId: i.id,
  }));
};

module.exports = {
  key: 'invoice_overdue',
  noun: 'Invoice',
  display: {
    label: 'Invoice Overdue',
    description: 'Triggers when an invoice becomes overdue.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: { ...samples.invoice, status: 'overdue' },
    outputFields: [{ key: 'invoiceRecordId', label: 'Invoice Record ID' }, ...invoiceOutputFields],
  },
};
