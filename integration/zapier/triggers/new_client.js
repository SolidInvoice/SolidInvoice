'use strict';

const { fetchCollection } = require('../utils/pagination');
const { clientOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) =>
  fetchCollection(z, bundle, '/api/clients', {
    'order[createdAt]': 'desc',
  });

module.exports = {
  key: 'new_client',
  noun: 'Client',
  display: {
    label: 'New Client',
    description: 'Triggers when a new client is created.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.client,
    outputFields: clientOutputFields,
  },
};
