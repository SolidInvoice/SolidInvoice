'use strict';

const { App, BASE_URL, createTester, makeBundle, hydraCollection, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('triggers.new_invoice', () => {
  it('returns the invoice members', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .get('/api/invoices')
      .query(true)
      .reply(200, hydraCollection([samples.invoice]));

    const results = await tester(App.triggers.new_invoice.operation.perform, makeBundle());

    expect(results).toHaveLength(1);
    expect(results[0].invoiceId).toBe('INV-0001');
  });
});
