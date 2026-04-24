'use strict';

const { App, BASE_URL, createTester, makeBundle, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('creates.create_client', () => {
  it('posts the client payload and returns the created client', async () => {
    const tester = createTester();

    nock(BASE_URL, {
      reqheaders: { 'X-API-TOKEN': 'test-token-123' },
    })
      .post('/api/clients', (body) => {
        expect(body.name).toBe('Acme Corp');
        expect(body.contacts).toHaveLength(1);
        expect(body.contacts[0].email).toBe('jane@acme.example');
        return true;
      })
      .reply(201, samples.client);

    const bundle = makeBundle({
      inputData: {
        name: 'Acme Corp',
        contact_firstName: 'Jane',
        contact_email: 'jane@acme.example',
      },
    });

    const result = await tester(App.creates.create_client.operation.perform, bundle);
    expect(result.name).toBe('Acme Corp');
  });
});
