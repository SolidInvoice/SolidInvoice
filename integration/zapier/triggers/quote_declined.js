'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { quoteOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const items = await fetchCollection(z, bundle, '/api/quotes', {
    status: 'declined',
    'order[updatedAt]': 'desc',
  });
  return items.map((q) => ({
    ...q,
    id: makeSyntheticId(q, 'declined', q.updatedAt),
    quoteRecordId: q.id,
  }));
};

module.exports = {
  key: 'quote_declined',
  noun: 'Quote',
  display: {
    label: 'Quote Declined',
    description: 'Triggers when a quote is declined.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: { ...samples.quote, status: 'declined' },
    outputFields: [{ key: 'quoteRecordId', label: 'Quote Record ID' }, ...quoteOutputFields],
  },
};
