'use strict';

const { fetchCollection } = require('../utils/pagination');
const { paymentOutputFields } = require('../utils/fields');
const samples = require('../utils/samples');

const perform = async (z, bundle) =>
  fetchCollection(z, bundle, '/api/payments', {
    'order[completed]': 'desc',
  });

module.exports = {
  key: 'new_payment',
  noun: 'Payment',
  display: {
    label: 'New Payment',
    description: 'Triggers when a new payment is recorded.',
  },
  operation: {
    type: 'polling',
    perform,
    canPaginate: true,
    sample: samples.payment,
    outputFields: paymentOutputFields,
  },
};
