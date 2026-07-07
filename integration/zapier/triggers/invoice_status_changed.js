'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { invoiceOutputFields, invoiceStatusField } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const extraParams = { 'order[updatedAt]': 'desc' };
  if (bundle.inputData && bundle.inputData.status) {
    extraParams.status = bundle.inputData.status;
  }
  const items = await fetchCollection(z, bundle, '/api/invoices', extraParams);
  return items.map((i) => ({
    ...i,
    id: makeSyntheticId(i, i.status, i.updatedAt),
    invoiceRecordId: i.id,
  }));
};

module.exports = {
  key: 'invoice_status_changed',
  noun: 'Invoice',
  display: {
    label: 'Invoice Status Changed',
    description:
      'Triggers when an invoice transitions to a specific status (or any status change if none is chosen).',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    inputFields: [invoiceStatusField(false)],
    sample: samples.invoice,
    outputFields: [{ key: 'invoiceRecordId', label: 'Invoice Record ID' }, ...invoiceOutputFields],
  },
};
