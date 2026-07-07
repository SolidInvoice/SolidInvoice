'use strict';

const { App, BASE_URL, createTester, makeBundle, hydraCollection, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('triggers.new_payment', () => {
  it('returns payment members', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .get('/api/payments')
      .query(true)
      .reply(200, hydraCollection([samples.payment]));

    const results = await tester(App.triggers.new_payment.operation.perform, makeBundle());
    expect(results).toHaveLength(1);
    expect(results[0].status).toBe('captured');
  });
});
