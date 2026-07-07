'use strict';

const client = {
  id: '01HXXGCC63QMFRAXCYPFZQRR1R',
  '@id': '/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R',
  '@type': 'Client',
  name: 'Acme Corp',
  website: 'https://acme.example',
  status: 'active',
  currencyCode: 'USD',
  vatNumber: null,
  credit: 0,
  contacts: ['/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R/contact/01HXXGCC63QPS18E4PPFZQRR1R'],
  addresses: [],
  invoices: [],
  quotes: [],
  payments: [],
  recurringInvoices: [],
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
};

const contact = {
  id: '01HXXGCC63QPS18E4PPFZQRR1R',
  '@id': '/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R/contact/01HXXGCC63QPS18E4PPFZQRR1R',
  '@type': 'Contact',
  firstName: 'Jane',
  lastName: 'Doe',
  email: 'jane@acme.example',
  type: 'primary',
};

const address = {
  id: '01HXXGCC63QPS18E4PPFZQRR1S',
  '@id': '/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R/address/01HXXGCC63QPS18E4PPFZQRR1S',
  '@type': 'Address',
  street1: '123 Main St',
  street2: null,
  city: 'San Francisco',
  state: 'CA',
  zip: '94105',
  country: 'US',
};

const invoice = {
  id: '01JTD2MKCQZ8BGRBHC2Z0Z4B8V',
  '@id': '/api/invoices/01JTD2MKCQZ8BGRBHC2Z0Z4B8V',
  '@type': 'Invoice',
  invoiceId: 'INV-0001',
  uuid: '01969a2a-4d7f-719d-9859-2aece4ccd517',
  status: 'pending',
  client: '/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R',
  total: 130000,
  baseTotal: 130000,
  tax: 0,
  balance: 130000,
  totalFormatted: '$1,300.00',
  balanceFormatted: '$1,300.00',
  invoiceDate: '2026-01-01T00:00:00+00:00',
  due: '2026-01-31T00:00:00+00:00',
  paidDate: null,
  payments: [],
  quote: null,
  terms: null,
  notes: null,
  discount: { type: null, value: 0 },
  lines: [
    {
      id: '01JTD2MKCQZ8BGRBHC2Z0Z4B8X',
      description: 'Consulting — June',
      price: 10000,
      qty: 13,
      tax: null,
      total: 130000,
    },
  ],
  users: ['/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R/contact/01HXXGCC63QPS18E4PPFZQRR1R'],
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
};

const quote = {
  id: '01JTD2MKCQZ8BGRBHC2Z0Z4Q0T',
  '@id': '/api/quotes/01JTD2MKCQZ8BGRBHC2Z0Z4Q0T',
  '@type': 'Quote',
  quoteId: 'QUO-0001',
  uuid: '01969a2a-4d7f-719d-9859-2aece4ccd517',
  status: 'pending',
  client: '/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R',
  total: 200000,
  baseTotal: 200000,
  tax: 0,
  due: '2026-02-01T00:00:00+00:00',
  terms: null,
  notes: null,
  discount: { type: null, value: 0 },
  lines: [
    {
      description: 'Website redesign',
      price: 200000,
      qty: 1,
      tax: null,
      total: 200000,
    },
  ],
  users: ['/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R/contact/01HXXGCC63QPS18E4PPFZQRR1R'],
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
};

const payment = {
  id: '01JSYWKFDJP0YCXQ8ZA33ABEQ6',
  '@id': '/api/payments/01JSYWKFDJP0YCXQ8ZA33ABEQ6',
  '@type': 'Payment',
  status: 'captured',
  totalAmount: 130000,
  currencyCode: 'USD',
  completed: '2026-01-15T12:45:35+00:00',
  message: null,
  reference: 'TXN-0001',
  client: '/api/clients/01HXXGCC63QMFRAXCYPFZQRR1R',
  invoice: '/api/invoices/01JTD2MKCQZ8BGRBHC2Z0Z4B8V',
  method: { name: 'Bank Transfer' },
};

const recurringInvoice = {
  ...invoice,
  id: '01JTD2MKCQZ8BGRBHC2Z0Z4R0R',
  '@id': '/api/recurring-invoices/01JTD2MKCQZ8BGRBHC2Z0Z4R0R',
  '@type': 'RecurringInvoice',
  status: 'active',
  frequency: '@monthly',
};

const tax = {
  id: '01JTD2MKCQZ8BGRBHC2Z0Z4T0T',
  '@id': '/api/taxes/01JTD2MKCQZ8BGRBHC2Z0Z4T0T',
  '@type': 'Tax',
  name: 'VAT',
  rate: 20,
  type: 'Inclusive',
};

module.exports = {
  client,
  contact,
  address,
  invoice,
  quote,
  payment,
  recurringInvoice,
  tax,
};
