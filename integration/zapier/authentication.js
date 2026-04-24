'use strict';

const { DEFAULT_INSTANCE_URL } = require('./constants');

const test = async (z, bundle) => {
  const response = await z.request({
    url: `${bundle.authData.server_url}/api/profile/api-tokens`,
    method: 'GET',
  });
  return response.data;
};

module.exports = {
  type: 'custom',
  fields: [
    {
      key: 'server_url',
      label: 'SolidInvoice URL',
      type: 'string',
      required: true,
      default: DEFAULT_INSTANCE_URL,
      helpText:
        'The URL of your SolidInvoice instance. Use `https://app.solidinvoice.co` for the hosted version, or your self-hosted URL (e.g. `https://billing.mycompany.com`). Do not include a trailing slash.',
    },
    {
      key: 'api_token',
      label: 'API Token',
      type: 'password',
      required: true,
      helpText:
        'Generate an API token in SolidInvoice under your user profile → "API Tokens" → "Create Token". The token is scoped to a single company.',
    },
  ],
  test,
  connectionLabel: (z, bundle) => {
    const host = (bundle.authData.server_url || '').replace(/^https?:\/\//, '');
    return host || 'SolidInvoice';
  },
};
