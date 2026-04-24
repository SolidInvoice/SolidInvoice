'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { invoiceOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const items = await fetchCollection(z, bundle, '/api/invoices', {
    status: 'paid',
    'order[paidDate]': 'desc',
  });
  return items.map((i) => ({
    ...i,
    id: makeSyntheticId(i, i.paidDate || i.updatedAt),
    invoiceRecordId: i.id,
  }));
};

module.exports = {
  key: 'invoice_paid',
  noun: 'Invoice',
  display: {
    label: 'Invoice Paid',
    description: 'Triggers when an invoice is marked paid.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: { ...samples.invoice, status: 'paid', paidDate: '2026-01-15T00:00:00+00:00' },
    outputFields: [{ key: 'invoiceRecordId', label: 'Invoice Record ID' }, ...invoiceOutputFields],
  },
};
