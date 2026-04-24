'use strict';

const nock = require('nock');
const zapier = require('zapier-platform-core');

const App = require('../index');

const BASE_URL = 'https://solidinvoice.test';

const createTester = () => zapier.createAppTester(App);

const makeBundle = (overrides = {}) => ({
  authData: {
    api_token: 'test-token-123',
    server_url: BASE_URL,
  },
  inputData: {},
  meta: { page: 0 },
  ...overrides,
});

const hydraCollection = (members) => ({
  '@context': '/api/contexts/Collection',
  '@id': '/api/clients',
  '@type': 'hydra:Collection',
  'hydra:member': members,
  'hydra:totalItems': members.length,
});

afterEach(() => {
  nock.cleanAll();
});

module.exports = {
  App,
  BASE_URL,
  createTester,
  makeBundle,
  hydraCollection,
  nock,
};
