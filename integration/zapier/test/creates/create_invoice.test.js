'use strict';

const { App, BASE_URL, createTester, makeBundle, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('creates.create_invoice', () => {
  it('converts line prices to cents and submits the invoice', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .post('/api/invoices', (body) => {
        expect(body.client).toBe('/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R');
        expect(body.lines).toHaveLength(1);
        expect(body.lines[0].price).toBe(10050);
        expect(body.lines[0].qty).toBe(2);
        return true;
      })
      .reply(201, samples.invoice);

    const bundle = makeBundle({
      inputData: {
        client: '01HXXGCC63QMFRAXCYPFZQRR1R',
        lines: [{ description: 'Work', price: '100.50', qty: 2 }],
      },
    });

    const result = await tester(App.creates.create_invoice.operation.perform, bundle);
    expect(result.invoiceId).toBe('INV-0001');
  });

  it('accepts a fully-qualified client IRI unchanged', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .post('/api/invoices', (body) => {
        expect(body.client).toBe('/api/clients/FROMIRI');
        return true;
      })
      .reply(201, samples.invoice);

    const bundle = makeBundle({
      inputData: {
        client: '/api/clients/FROMIRI',
        lines: [{ description: 'Work', price: '10', qty: 1 }],
      },
    });

    await tester(App.creates.create_invoice.operation.perform, bundle);
  });
});
