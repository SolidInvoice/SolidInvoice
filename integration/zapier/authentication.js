'use strict';

const { DEFAULT_INSTANCE_URL } = require('./constants');

const normalizeUrl = (z, rawUrl) => {
  const url = (rawUrl || '').trim().replace(/\/+$/, '');
  if (!/^https:\/\//i.test(url)) {
    throw new z.errors.Error(
      `SolidInvoice URL must start with https:// (got "${rawUrl}"). Zapier requires TLS for all API traffic.`,
      'AuthenticationError',
      400
    );
  }
  return url;
};

const fingerprintToken = (token) => {
  if (!token) return '<missing>';
  const t = String(token);
  if (t.length < 12) return `<len=${t.length}>`;
  return `${t.slice(0, 4)}…${t.slice(-4)} (len=${t.length})`;
};

const test = async (z, bundle) => {
  const base = normalizeUrl(z, bundle.authData.server_url);
  const tokenFingerprint = fingerprintToken(bundle.authData.api_token);

  z.console.log('SolidInvoice auth test ->', {
    url: `${base}/api/clients?itemsPerPage=1`,
    tokenFingerprint,
    hasAuthData: !!bundle.authData,
    authDataKeys: Object.keys(bundle.authData || {}),
  });

  const response = await z.request({
    url: `${base}/api/clients`,
    method: 'GET',
    headers: {
      'X-API-TOKEN': bundle.authData.api_token,
      Accept: 'application/ld+json',
      'User-Agent': 'SolidInvoice-Zapier/1.0 (+https://zapier.com)',
      'X-Zapier-Auth-Test': '1',
    },
    params: { itemsPerPage: 1 },
    skipThrowForStatus: true,
  });

  z.console.log('SolidInvoice auth test <-', {
    status: response.status,
    requestHeadersSent: Object.keys(response.request?.headers || {}),
    hasXApiTokenHeader: !!(response.request?.headers?.['X-API-TOKEN'] || response.request?.headers?.['x-api-token']),
    responseHeaders: response.headers || {},
    bodyPreview: (response.content || '').slice(0, 600),
  });

  if (response.status === 401 || response.status === 403) {
    let detail;
    try {
      const parsed = z.JSON.parse(response.content);
      detail = parsed['hydra:description'] || parsed.detail || parsed.message || response.content;
    } catch {
      detail = response.content || `HTTP ${response.status}`;
    }
    throw new z.errors.Error(
      `SolidInvoice rejected the API token (HTTP ${response.status}). Detail: ${detail}. Token fingerprint: ${tokenFingerprint}. URL: ${base}/api/clients`,
      'AuthenticationError',
      response.status
    );
  }
  if (response.status >= 400) {
    throw new z.errors.Error(
      `SolidInvoice returned HTTP ${response.status}: ${response.content?.slice(0, 200)}`,
      'ApiError',
      response.status
    );
  }
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
        'The HTTPS URL of your SolidInvoice instance. Use `https://solidinvoice.app` for the hosted version, or your self-hosted URL (e.g. `https://billing.mycompany.com`). HTTP URLs are not supported; TLS is required.',
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
