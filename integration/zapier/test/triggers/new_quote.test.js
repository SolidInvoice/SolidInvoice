'use strict';

const { App, BASE_URL, createTester, makeBundle, hydraCollection, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('triggers.new_quote', () => {
  it('returns the quote members', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .get('/api/quotes')
      .query(true)
      .reply(200, hydraCollection([samples.quote]));

    const results = await tester(App.triggers.new_quote.operation.perform, makeBundle());
    expect(results).toHaveLength(1);
    expect(results[0].quoteId).toBe('QUO-0001');
  });
});
