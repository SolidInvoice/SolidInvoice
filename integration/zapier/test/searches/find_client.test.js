'use strict';

const { App, BASE_URL, createTester, makeBundle, hydraCollection, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('searches.find_client', () => {
  it('filters by name', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .get('/api/clients')
      .query((q) => q.name === 'Acme')
      .reply(200, hydraCollection([samples.client]));

    const results = await tester(App.searches.find_client.operation.perform, makeBundle({
      inputData: { name: 'Acme' },
    }));
    expect(results).toHaveLength(1);
  });

  it('returns empty array when nothing matches', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .get('/api/clients')
      .query(true)
      .reply(200, hydraCollection([]));

    const results = await tester(App.searches.find_client.operation.perform, makeBundle({
      inputData: { name: 'Nope' },
    }));
    expect(results).toEqual([]);
  });
});
