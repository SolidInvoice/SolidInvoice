'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { quoteOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const items = await fetchCollection(z, bundle, '/api/quotes', {
    'order[updatedAt]': 'desc',
  });
  return items.map((q) => ({
    ...q,
    id: makeSyntheticId(q, q.updatedAt, q.status),
    quoteRecordId: q.id,
  }));
};

module.exports = {
  key: 'updated_quote',
  noun: 'Quote',
  display: {
    label: 'Updated Quote',
    description: 'Triggers when a quote is updated.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.quote,
    outputFields: [{ key: 'quoteRecordId', label: 'Quote Record ID' }, ...quoteOutputFields],
  },
};
