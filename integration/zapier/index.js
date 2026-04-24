'use strict';

const authentication = require('./authentication');
const { beforeRequest, afterResponse } = require('./middleware');

const newClient = require('./triggers/new_client');
const updatedClient = require('./triggers/updated_client');
const newContact = require('./triggers/new_contact');
const newInvoice = require('./triggers/new_invoice');
const updatedInvoice = require('./triggers/updated_invoice');
const invoiceStatusChanged = require('./triggers/invoice_status_changed');
const invoicePaid = require('./triggers/invoice_paid');
const invoiceOverdue = require('./triggers/invoice_overdue');
const newQuote = require('./triggers/new_quote');
const updatedQuote = require('./triggers/updated_quote');
const quoteAccepted = require('./triggers/quote_accepted');
const quoteDeclined = require('./triggers/quote_declined');
const newPayment = require('./triggers/new_payment');
const paymentCompleted = require('./triggers/payment_completed');
const newRecurringInvoice = require('./triggers/new_recurring_invoice');
const newTaxRate = require('./triggers/new_tax_rate');

const createClient = require('./creates/create_client');
const createContact = require('./creates/create_contact');
const createAddress = require('./creates/create_address');
const createInvoice = require('./creates/create_invoice');
const updateInvoice = require('./creates/update_invoice');
const transitionInvoice = require('./creates/transition_invoice');
const sendInvoice = require('./creates/send_invoice');
const createQuote = require('./creates/create_quote');
const updateQuote = require('./creates/update_quote');
const transitionQuote = require('./creates/transition_quote');
const convertQuoteToInvoice = require('./creates/convert_quote_to_invoice');
const createPayment = require('./creates/create_payment');
const createTaxRate = require('./creates/create_tax_rate');

const findClient = require('./searches/find_client');
const findContact = require('./searches/find_contact');
const findInvoice = require('./searches/find_invoice');
const findQuote = require('./searches/find_quote');
const findPayment = require('./searches/find_payment');

const findOrCreateClient = require('./searches_or_creates/find_or_create_client');

const register = (items) =>
  items.reduce((acc, item) => {
    acc[item.key] = item;
    return acc;
  }, {});

module.exports = {
  platformVersion: require('zapier-platform-core').version,
  version: require('./package.json').version,
  authentication,

  beforeRequest: [beforeRequest],
  afterResponse: [afterResponse],

  triggers: register([
    newClient,
    updatedClient,
    newContact,
    newInvoice,
    updatedInvoice,
    invoiceStatusChanged,
    invoicePaid,
    invoiceOverdue,
    newQuote,
    updatedQuote,
    quoteAccepted,
    quoteDeclined,
    newPayment,
    paymentCompleted,
    newRecurringInvoice,
    newTaxRate,
  ]),

  creates: register([
    createClient,
    createContact,
    createAddress,
    createInvoice,
    updateInvoice,
    transitionInvoice,
    sendInvoice,
    createQuote,
    updateQuote,
    transitionQuote,
    convertQuoteToInvoice,
    createPayment,
    createTaxRate,
  ]),

  searches: register([findClient, findContact, findInvoice, findQuote, findPayment]),

  searchOrCreates: register([findOrCreateClient]),

  flags: { skipThrowForStatus: true, cleanInputData: false },
};
