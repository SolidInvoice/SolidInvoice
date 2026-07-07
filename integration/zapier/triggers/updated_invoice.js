'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const items = await fetchCollection(z, bundle, '/api/invoices', {
    'order[updatedAt]': 'desc',
  });
  return items.map((i) => ({
    ...i,
    id: makeSyntheticId(i, i.updatedAt, i.status),
    invoiceRecordId: i.id,
  }));
};

module.exports = {
  key: 'updated_invoice',
  noun: 'Invoice',
  display: {
    label: 'Updated Invoice',
    description:
      'Triggers when an invoice is updated (any field change or status transition).',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.invoice,
    outputFields: [{ key: 'invoiceRecordId', label: 'Invoice Record ID' }, ...invoiceOutputFields],
  },
};
