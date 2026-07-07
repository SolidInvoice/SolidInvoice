'use strict';

const { App, BASE_URL, createTester, makeBundle, nock } = require('../helper');
const samples = require('../../utils/samples');

describe('creates.transition_invoice', () => {
  it('posts to the transition endpoint', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .post('/api/invoices/01JTD2MKCQZ8BGRBHC2Z0Z4B8V/transitions/pay')
      .reply(200, { ...samples.invoice, status: 'paid' });

    const bundle = makeBundle({
      inputData: {
        invoice: '01JTD2MKCQZ8BGRBHC2Z0Z4B8V',
        transition: 'pay',
      },
    });

    const result = await tester(App.creates.transition_invoice.operation.perform, bundle);
    expect(result.status).toBe('paid');
  });

  it('surfaces validation errors from an invalid transition', async () => {
    const tester = createTester();

    nock(BASE_URL)
      .post('/api/invoices/01JTD2MKCQZ8BGRBHC2Z0Z4B8V/transitions/pay')
      .reply(422, {
        '@context': '/api/contexts/ConstraintViolationList',
        '@type': 'ConstraintViolationList',
        'hydra:title': 'Unable to apply transition',
        'hydra:description': 'Transition "pay" is not allowed from status "draft"',
      });

    const bundle = makeBundle({
      inputData: { invoice: '01JTD2MKCQZ8BGRBHC2Z0Z4B8V', transition: 'pay' },
    });

    await expect(
      tester(App.creates.transition_invoice.operation.perform, bundle)
    ).rejects.toThrow(/not allowed from status/);
  });
});
