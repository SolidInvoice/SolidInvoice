'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { clientOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const clients = await fetchCollection(z, bundle, '/api/clients', {
    'order[updatedAt]': 'desc',
  });
  return clients.map((c) => ({
    ...c,
    id: makeSyntheticId(c, c.updatedAt),
    clientId: c.id,
  }));
};

module.exports = {
  key: 'updated_client',
  noun: 'Client',
  display: {
    label: 'Updated Client',
    description:
      'Triggers when a client is updated. Fires on every change, deduped by the updated-at timestamp.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.client,
    outputFields: [{ key: 'clientId', label: 'Client ID' }, ...clientOutputFields],
  },
};
