'use strict';

const { fetchCollection } = require('../utils/pagination');
const { quoteOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) =>
  fetchCollection(z, bundle, '/api/quotes', {
    'order[createdAt]': 'desc',
  });

module.exports = {
  key: 'new_quote',
  noun: 'Quote',
  display: {
    label: 'New Quote',
    description: 'Triggers when a new quote is created.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.quote,
    outputFields: quoteOutputFields,
  },
};
