'use strict';

const { DEFAULT_INSTANCE_URL } = require('./constants');

const normalizeUrl = (z, rawUrl) => {
  const url = (rawUrl || '').trim().replace(/\/+$/, '');
  if (!/^https?:\/\//i.test(url)) {
    throw new z.errors.Error(
      `SolidInvoice URL must start with http:// or https:// (got "${rawUrl}").`,
      'AuthenticationError',
      400
    );
  }
  return url;
};

const test = async (z, bundle) => {
  const base = normalizeUrl(z, bundle.authData.server_url);
  const response = await z.request({
    url: `${base}/api/profile/api-tokens`,
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
        'The URL of your SolidInvoice instance, including `https://`. Use `https://app.solidinvoice.co` for the hosted version, or your self-hosted URL (e.g. `https://billing.mycompany.com`). Do not include a trailing slash or API path.',
    },
    {
      key: 'api_token',
      label: 'API Token',
      type: 'password',
      required: true,
      helpText:
        'Generate an API token at `{your-instance}/profile/api` → "Create Token". The token is scoped to the issuing user\'s company.',
    },
  ],
  test,
  connectionLabel: (z, bundle) => {
    const host = (bundle.authData.server_url || '').replace(/^https?:\/\//, '').replace(/\/+$/, '');
    return host || 'SolidInvoice';
  },
};
