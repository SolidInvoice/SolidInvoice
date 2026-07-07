'use strict';

const { DEFAULT_PAGE_SIZE } = require('../constants');

const fetchCollection = async (z, bundle, path, extraParams = {}) => {
  const page = (bundle.meta && bundle.meta.page ? bundle.meta.page : 0) + 1;
  const response = await z.request({
    url: `${bundle.authData.server_url}${path}`,
    method: 'GET',
    params: {
      page,
      itemsPerPage: DEFAULT_PAGE_SIZE,
      ...extraParams,
    },
  });

  const data = response.data || {};
  const members = data['hydra:member'] || data.member || data || [];
  return Array.isArray(members) ? members : [];
};

const resolveIriId = (iriOrId) => {
  if (!iriOrId) return null;
  const match = String(iriOrId).match(/\/([^/]+)\/?$/);
  return match ? match[1] : String(iriOrId);
};

const makeSyntheticId = (item, ...suffixes) => {
  const base = item['@id'] || item.id || '';
  const parts = [base, ...suffixes.filter((p) => p !== undefined && p !== null)];
  return parts.join('|');
};

module.exports = {
  fetchCollection,
  resolveIriId,
  makeSyntheticId,
};
