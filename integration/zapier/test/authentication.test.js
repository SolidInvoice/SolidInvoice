'use strict';

const { App, BASE_URL, createTester, makeBundle, nock } = require('./helper');

describe('authentication', () => {
  it('passes the test endpoint when the token works', async () => {
    const tester = createTester();

    nock(BASE_URL, {
      reqheaders: { 'X-API-TOKEN': 'test-token-123' },
    })
      .get('/api/profile/api-tokens')
      .reply(200, {
        '@context': '/api/contexts/Collection',
        'hydra:member': [{ id: 'tok_1', name: 'Zapier' }],
      });

    const result = await tester(App.authentication.test, makeBundle());
    expect(result['hydra:member']).toBeDefined();
  });

  it('rejects plain-http URLs', async () => {
    const tester = createTester();
    const bundle = makeBundle({ authData: { api_token: 'x', server_url: 'http://insecure.test' } });
    await expect(tester(App.authentication.test, bundle)).rejects.toThrow(/must start with https/);
  });

  it('throws RefreshAuthError on a 401', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .get('/api/profile/api-tokens')
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
