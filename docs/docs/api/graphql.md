---
title: GraphQL API
description: Query and mutate SolidInvoice data using the GraphQL API.
sidebar_position: 2
---

# GraphQL API

SolidInvoice's GraphQL API gives you a flexible, typed interface to the same data as the REST API. Instead of calling multiple fixed endpoints, you write a single query that describes exactly what you need — and the server returns precisely that, nothing more.

The GraphQL endpoint is available at `/api/graphql` on your SolidInvoice installation.

:::info[Hosted vs self-hosted]
If you're on the **hosted SolidInvoice plan**, the endpoint is:

```text
https://solidinvoice.app/api/graphql
```

For a **self-hosted instance**, replace the domain with your own:

```text
https://your-domain.example/api/graphql
```

:::

:::tip
Not sure whether to use REST or GraphQL? Use **REST** if you're integrating with automation tools like Zapier or n8n, or calling simple individual resources. Use **GraphQL** when you need to fetch related data in one request, or want fine-grained control over the response shape.
:::

## Interactive explorer (GraphiQL)

Opening `/api/graphql` in a browser loads **GraphiQL**, an in-browser IDE for building and testing queries. On the hosted plan that's [solidinvoice.app/api/graphql](https://solidinvoice.app/api/graphql); on a self-hosted instance use your own domain. It includes:

- A query editor with syntax highlighting and autocomplete
- Inline documentation for every type and field
- A history panel showing your recent queries
- Variable and header editors

GraphiQL is the fastest way to explore what's available — use the `Docs` panel on the right to browse all types, queries, and mutations.

## Authentication

GraphQL uses the same API token authentication as the REST API. Create a token at `Settings` → `API Keys` (see [Creating an API token](./rest-api.md#creating-an-api-token)), then send it in the `X-API-TOKEN` header on every request.

```bash
curl -X POST https://solidinvoice.app/api/graphql \
     -H "X-API-TOKEN: <your-token>" \
     -H "Content-Type: application/json" \
     -d '{"query": "{ invoices { edges { node { id status } } } }"}'
```

In GraphiQL, add the header under the `Headers` tab at the bottom of the editor:

```json
{
  "X-API-TOKEN": "<your-token>"
}
```

Requests without a valid token receive a `401 Unauthorized` response.

:::info
Tokens are scoped to one user and one company. If your account has multiple companies, generate a separate token for each by switching companies before creating the token.
:::

## Querying data

GraphQL queries are sent as HTTP `POST` requests to `/api/graphql` with a JSON body containing a `query` field.

### Fetching a collection

Use the plural resource name to fetch a list. Each collection returns a [Relay-style connection](#pagination) with an `edges` wrapper:

```graphql
query {
  invoices {
    edges {
      node {
        id
        status
        total
      }
    }
  }
}
```

```bash
curl -X POST https://solidinvoice.app/api/graphql \
     -H "X-API-TOKEN: <your-token>" \
     -H "Content-Type: application/json" \
     -d '{
       "query": "{ invoices { edges { node { id status total } } } }"
     }'
```

### Fetching a single item

Use the singular resource name with an `id` argument. The ID must be the full IRI string (e.g. `/api/invoices/01J...`):

```graphql
query {
  invoice(id: "/api/invoices/01JDKR4XQ3NEVF8CNKQSJ5GPRT") {
    id
    status
    total
    client {
      name
    }
  }
}
```

### Fetching related data

One of GraphQL's key advantages is requesting related resources in a single round-trip. The following query fetches invoices together with their client name and line items in one request:

```graphql
query {
  invoices {
    edges {
      node {
        id
        status
        total
        client {
          name
          currency
        }
        lines {
          edges {
            node {
              description
              qty
              price
            }
          }
        }
      }
    }
  }
}
```

## Filtering collections

Pass filter arguments directly to the collection query. The available filters match those on the REST API for each resource.

### Filter invoices by status

```graphql
query {
  invoices(status: "pending") {
    edges {
      node {
        id
        status
        total
      }
    }
  }
}
```

### Filter clients by name

```graphql
query {
  clients(name: "Acme") {
    edges {
      node {
        id
        name
      }
    }
  }
}
```

### Using variables

For dynamic queries, pass filter values as GraphQL variables rather than inlining them:

```graphql
query GetInvoicesByStatus($status: String) {
  invoices(status: $status) {
    edges {
      node {
        id
        status
        total
      }
    }
  }
}
```

Send the variables in the `variables` field of the request body:

```bash
curl -X POST https://solidinvoice.app/api/graphql \
     -H "X-API-TOKEN: <your-token>" \
     -H "Content-Type: application/json" \
     -d '{
       "query": "query GetInvoicesByStatus($status: String) { invoices(status: $status) { edges { node { id status total } } } }",
       "variables": { "status": "pending" }
     }'
```

## Mutations

Mutations create, update, or delete resources. They follow a consistent naming pattern:

| Operation | Mutation name pattern | Example |
| --- | --- | --- |
| Create | `create{Resource}` | `createClient` |
| Update | `update{Resource}` | `updateInvoice` |
| Delete | `delete{Resource}` | `deleteQuote` |

### Creating a resource

Pass the fields in an `input` argument. The mutation returns the created resource:

```graphql
mutation {
  createClient(input: {
    name: "Acme Corp"
    currency: "USD"
    website: "https://acme.example"
  }) {
    client {
      id
      name
    }
  }
}
```

### Updating a resource

Provide the `id` (full IRI) and only the fields you want to change:

```graphql
mutation {
  updateClient(input: {
    id: "/api/clients/01JDKR4XQ3NEVF8CNKQSJ5GPRT"
    website: "https://new-site.example"
  }) {
    client {
      id
      website
    }
  }
}
```

### Deleting a resource

```graphql
mutation {
  deleteInvoice(input: {
    id: "/api/invoices/01JDKR4XQ3NEVF8CNKQSJ5GPRT"
  }) {
    invoice {
      id
    }
  }
}
```

:::warning
Deletion is immediate and cannot be undone through the API. Make sure you have the correct `id` before running a delete mutation.
:::

## Pagination

GraphQL collections use **cursor-based pagination** via the Relay connection spec. Each collection query accepts `first`, `last`, `before`, and `after` arguments, and returns `pageInfo` alongside the edges:

```graphql
query {
  invoices(first: 10, after: "cursor-value-from-previous-page") {
    pageInfo {
      hasNextPage
      hasPreviousPage
      startCursor
      endCursor
    }
    edges {
      cursor
      node {
        id
        status
        total
      }
    }
  }
}
```

To page forward through results:

1. Run the query without `after` to get the first page.
2. Check `pageInfo.hasNextPage`. If `true`, pass `pageInfo.endCursor` as the `after` argument in your next request.
3. Repeat until `hasNextPage` is `false`.

The default page size is **30 items**. Pass a `first` argument to request fewer (maximum 30 per page):

```graphql
query {
  invoices(first: 5) {
    edges {
      node { id status }
    }
  }
}
```

## Available resources

All core resources are available via GraphQL. API token management is REST-only and cannot be accessed through the GraphQL API.

| Resource | Query (collection) | Query (single) | Mutations |
| --- | --- | --- | --- |
| Clients | `clients` | `client(id:)` | `createClient`, `updateClient`, `deleteClient` |
| Contacts | `contacts` | `contact(id:)` | `createContact`, `updateContact`, `deleteContact` |
| Addresses | `addresses` | `address(id:)` | `createAddress`, `updateAddress`, `deleteAddress` |
| Invoices | `invoices` | `invoice(id:)` | `createInvoice`, `updateInvoice`, `deleteInvoice` |
| Invoice lines | `invoiceLines` | `invoiceLine(id:)` | `createInvoiceLine`, `updateInvoiceLine`, `deleteInvoiceLine` |
| Recurring invoices | `recurringInvoices` | `recurringInvoice(id:)` | `createRecurringInvoice`, `updateRecurringInvoice`, `deleteRecurringInvoice` |
| Quotes | `quotes` | `quote(id:)` | `createQuote`, `updateQuote`, `deleteQuote` |
| Quote lines | `quoteLines` | `quoteLine(id:)` | `createQuoteLine`, `updateQuoteLine`, `deleteQuoteLine` |
| Payments | `payments` | `payment(id:)` | `createPayment` |
| Taxes | `taxes` | `tax(id:)` | `createTax`, `updateTax`, `deleteTax` |

:::info
Monetary amounts (totals, prices, balances) are always integers in the **smallest currency unit** — cents for USD/EUR, pence for GBP, etc. For example, `1000` represents `$10.00`. The currency itself comes from the associated client.
:::

## Introspection

GraphQL's introspection system lets you query the schema itself to discover all available types, fields, and operations. GraphiQL uses introspection automatically, but you can also query it directly:

```graphql
query {
  __schema {
    types {
      name
      kind
    }
  }
}
```

To inspect a specific type:

```graphql
query {
  __type(name: "Invoice") {
    fields {
      name
      type {
        name
        kind
      }
    }
  }
}
```

## Troubleshooting

### `401 Unauthorized`

The `X-API-TOKEN` header is missing, incorrect, or the token has been revoked. Verify the header name and value — the header must be `X-API-TOKEN` (not `Authorization` or `Bearer`). If the token no longer works, generate a new one from `Settings` → `API Keys`.

### Query returns `null` for a resource

The resource either doesn't exist, was deleted, or the token's company doesn't own it. Tokens are company-scoped — if you have multiple companies, make sure the token was created while the correct company was active.

### Mutation fails with a validation error

Check the `errors` array in the response. Each error includes a `message` and an `extensions.violations` array listing the specific field that failed validation and why:

```json
{
  "errors": [{
    "message": "name: This value should not be blank.",
    "extensions": {
      "violations": [
        { "path": "name", "message": "This value should not be blank." }
      ]
    }
  }]
}
```

### GraphiQL shows a blank page or won't load

GraphiQL is served at `/api/graphql` and requires a browser. If you're getting a blank page, check your browser console for JavaScript errors and make sure the page is not being blocked by a content security policy on your SolidInvoice instance.

### API token management operations fail

API token management (listing, creating, and revoking tokens) is not available via GraphQL — use the [REST API](./rest-api.md) or the `Settings` → `API Keys` page in the UI instead.
