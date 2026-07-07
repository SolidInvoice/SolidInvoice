'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { quoteOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const items = await fetchCollection(z, bundle, '/api/quotes', {
    status: 'accepted',
    'order[updatedAt]': 'desc',
  });
  return items.map((q) => ({
    ...q,
    id: makeSyntheticId(q, 'accepted', q.updatedAt),
    quoteRecordId: q.id,
  }));
};

module.exports = {
  key: 'quote_accepted',
  noun: 'Quote',
  display: {
    label: 'Quote Accepted',
    description: 'Triggers when a quote is accepted.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: { ...samples.quote, status: 'accepted' },
    outputFields: [{ key: 'quoteRecordId', label: 'Quote Record ID' }, ...quoteOutputFields],
  },
};
