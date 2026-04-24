'use strict';

const { App, BASE_URL, createTester, makeBundle, hydraCollection, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('triggers.new_client', () => {
  it('fetches clients with the expected headers and returns the members array', async () => {
    const tester = createTester();

    nock(BASE_URL, {
      reqheaders: { 'X-API-TOKEN': 'test-token-123' },
    })
      .get('/api/clients')
      .query((q) => q.page === '1' && q['order[createdAt]'] === 'desc')
      .reply(200, hydraCollection([samples.client]));

    const results = await tester(App.triggers.new_client.operation.perform, makeBundle());

    expect(results).toHaveLength(1);
    expect(results[0].name).toBe('Acme Corp');
  });
});
