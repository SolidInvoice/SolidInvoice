'use strict';

const { choices } = require('../constants');

const clientDropdown = (required = true) => ({
  key: 'client',
  label: 'Client',
  type: 'string',
  required,
  dynamic: 'new_client.id.name',
  search: 'find_client.id',
  helpText: 'The client this record belongs to.',
});

const currencyField = (required = false) => ({
  key: 'currency',
  label: 'Currency',
  type: 'string',
  required,
  placeholder: 'USD',
  helpText: 'Three-letter ISO currency code (e.g. USD, EUR, GBP). Leave empty to use the client default.',
});

const invoiceStatusField = (required = false) => ({
  key: 'status',
  label: 'Status',
  type: 'string',
  required,
  choices: choices.invoiceStatus,
});

const quoteStatusField = (required = false) => ({
  key: 'status',
  label: 'Status',
  type: 'string',
  required,
  choices: choices.quoteStatus,
});

const invoiceOutputFields = [
  { key: 'id', label: 'ID' },
  { key: 'invoiceId', label: 'Invoice Number' },
  { key: 'uuid', label: 'UUID' },
  { key: 'status', label: 'Status' },
  { key: 'total', label: 'Total (cents)', type: 'integer' },
  { key: 'baseTotal', label: 'Base Total (cents)', type: 'integer' },
  { key: 'tax', label: 'Tax (cents)', type: 'integer' },
  { key: 'balance', label: 'Balance (cents)', type: 'integer' },
  { key: 'totalFormatted', label: 'Total', type: 'string' },
  { key: 'balanceFormatted', label: 'Balance', type: 'string' },
  { key: 'client', label: 'Client IRI' },
  { key: 'invoiceDate', label: 'Invoice Date', type: 'datetime' },
  { key: 'due', label: 'Due Date', type: 'datetime' },
  { key: 'paidDate', label: 'Paid Date', type: 'datetime' },
  { key: 'notes', label: 'Notes' },
  { key: 'terms', label: 'Terms' },
];

const quoteOutputFields = [
  { key: 'id', label: 'ID' },
  { key: 'quoteId', label: 'Quote Number' },
  { key: 'uuid', label: 'UUID' },
  { key: 'status', label: 'Status' },
  { key: 'total', label: 'Total (cents)', type: 'integer' },
  { key: 'baseTotal', label: 'Base Total (cents)', type: 'integer' },
  { key: 'tax', label: 'Tax (cents)', type: 'integer' },
  { key: 'client', label: 'Client IRI' },
  { key: 'due', label: 'Due Date', type: 'datetime' },
  { key: 'notes', label: 'Notes' },
  { key: 'terms', label: 'Terms' },
];

const clientOutputFields = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Name' },
  { key: 'website', label: 'Website' },
  { key: 'status', label: 'Status' },
  { key: 'currencyCode', label: 'Currency' },
  { key: 'vatNumber', label: 'VAT Number' },
  { key: 'credit', label: 'Credit (cents)', type: 'integer' },
];

const paymentOutputFields = [
  { key: 'id', label: 'ID' },
  { key: 'status', label: 'Status' },
  { key: 'totalAmount', label: 'Amount (cents)', type: 'integer' },
  { key: 'currencyCode', label: 'Currency' },
  { key: 'completed', label: 'Completed', type: 'datetime' },
  { key: 'reference', label: 'Reference' },
  { key: 'client', label: 'Client IRI' },
  { key: 'invoice', label: 'Invoice IRI' },
  { key: 'method__name', label: 'Payment Method' },
];

module.exports = {
  clientDropdown,
  currencyField,
  invoiceStatusField,
  quoteStatusField,
  invoiceOutputFields,
  quoteOutputFields,
  clientOutputFields,
  paymentOutputFields,
};
