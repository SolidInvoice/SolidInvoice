'use strict';

const { fetchCollection, makeSyntheticId } = require('../utils/pagination');
const { paymentOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) => {
  const items = await fetchCollection(z, bundle, '/api/payments', {
    status: 'captured',
    'order[completed]': 'desc',
  });
  return items.map((p) => ({
    ...p,
    id: makeSyntheticId(p, 'captured', p.completed),
    paymentRecordId: p.id,
  }));
};

module.exports = {
  key: 'payment_completed',
  noun: 'Payment',
  display: {
    label: 'Payment Completed',
    description: 'Triggers when a payment is successfully captured.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: { ...samples.payment, status: 'captured' },
    outputFields: [{ key: 'paymentRecordId', label: 'Payment Record ID' }, ...paymentOutputFields],
  },
};
