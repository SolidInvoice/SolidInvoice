'use strict';

const { App, BASE_URL, createTester, makeBundle, hydraCollection, nock } = require('./helper');
const samples = require('../utils/samples');

describe('authentication', () => {
  it('passes the test endpoint when the token works', async () => {
    const tester = createTester();

    nock(BASE_URL, {
      reqheaders: { 'X-API-TOKEN': 'test-token-123' },
    })
      .get('/api/clients')
      .query(true)
      .reply(200, hydraCollection([samples.client]));

    const result = await tester(App.authentication.test, makeBundle());
    expect(result['hydra:member']).toBeDefined();
  });

  it('rejects plain-http URLs', async () => {
    const tester = createTester();
    const bundle = makeBundle({ authData: { api_token: 'x', server_url: 'http://insecure.test' } });
    await expect(tester(App.authentication.test, bundle)).rejects.toThrow(/must start with https/);
  });

  it('surfaces an ExpiredAuthError on a 401 so Zapier prompts reconnection', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .get('/api/clients')
      .query(true)
      .reply(401, {
        '@type': 'hydra:Error',
        'hydra:title': 'Unauthorized',
        'hydra:description': 'Invalid API token.',
      });

    await expect(tester(App.authentication.test, makeBundle())).rejects.toThrow(
      /Invalid API token/
    );
  });
});
