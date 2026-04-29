'use strict';

const beforeRequest = (request, z, bundle) => {
  request.headers = request.headers || {};
  if (!request.headers['X-API-TOKEN'] && bundle.authData && bundle.authData.api_token) {
    request.headers['X-API-TOKEN'] = bundle.authData.api_token;
  }
  if (!request.headers.Accept) {
    request.headers.Accept = 'application/ld+json';
  }
  if (request.method === 'PATCH') {
    request.headers['Content-Type'] = 'application/merge-patch+json';
  }
  if (request.url) {
    request.url = request.url.replace(/([^:])\/{2,}/g, '$1/');
  }
  return request;
};

const afterResponse = (response, z) => {
  if (response.status < 400) {
    return response;
  }

  if (response.request && response.request.headers && response.request.headers['X-Zapier-Auth-Test']) {
    return response;
  }

  let data = response.data;
  if (!data && response.content) {
    try {
      data = z.JSON.parse(response.content);
    } catch (_) {
      data = {};
    }
  }
  data = data || {};

  const hydraDescription = data['hydra:description'] || data.detail || data.message;
  const title = data['hydra:title'] || data.title;

  if (response.status === 401 || response.status === 403) {
    throw new z.errors.ExpiredAuthError(
      hydraDescription || 'The API token is invalid, expired, or does not have access to this resource. Reconnect the SolidInvoice account in Zapier.'
    );
  }

  if (response.status === 422 && data.violations) {
    const details = data.violations
      .map((v) => `${v.propertyPath}: ${v.message}`)
      .join('; ');
    throw new z.errors.Error(
      `Validation failed: ${details}`,
      'ValidationError',
      response.status
    );
  }

  const message = [title, hydraDescription].filter(Boolean).join(': ') ||
    `SolidInvoice returned HTTP ${response.status}`;
  throw new z.errors.Error(message, 'ApiError', response.status);
};

module.exports = { beforeRequest, afterResponse };
