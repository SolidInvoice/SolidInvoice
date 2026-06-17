---
title: Custom fields
description: Add extra fields to clients, contacts, invoices, and quotes to capture information specific to your business.
sidebar_position: 6
---

# Custom fields

Custom fields let you store additional information on clients, contacts, invoices, and quotes — things like account numbers, project codes, or any other data your business needs.

## Manage custom field definitions

Go to **Settings → Custom Fields** to see all defined fields. From here you can add, edit, reorder, and delete fields.

## Add a custom field

Click `Add Custom Field` to open the creation form.

### Applies to

Choose which record type the field appears on:

- **Client** — shown on the client create and edit forms
- **Contact** — shown when adding or editing a contact
- **Invoice** — shown on invoice create and edit forms
- **Quote** — shown on quote create and edit forms

### Label

The name shown next to the field in the UI and on PDFs. Maximum 125 characters.

### Type

| Type | Description |
| --- | --- |
| **Text** | Single-line text |
| **Long text** | Multi-line textarea |
| **Number** | Numeric input |
| **Date** | Date picker |
| **Email** | Email address with format validation |
| **URL** | Web address with format validation |
| **Checkbox** | True/false toggle |
| **Single-select** | Drop-down with one choice |
| **Multi-select** | Drop-down with multiple choices |

For **Single-select** and **Multi-select**, add the available options below the type selector using the options list. Each option gets a label and an auto-generated value.

### Required

Check **Required** to make the field mandatory when creating or editing the record.

### Visibility

Available for **Invoice** and **Quote** fields only:

| Option | Where the field appears |
| --- | --- |
| **Internal** | Admin views only — not shown on PDFs or the client-facing invoice/quote page |
| **Client-visible** | Admin views and PDFs and the client-facing page |

### Default value

Optionally pre-fill new records with this value.

## Filling in custom field values

Once a field is defined, it appears on the relevant create and edit forms automatically. For **Invoice** and **Quote** fields, client-visible values are printed on the generated PDF.

## Reordering fields

Drag and drop the fields in the list to change the order they appear in forms and on PDFs.

## Deleting a field

Click the delete action on a field in the list. Deleting a field removes its definition and all stored values across all records — this cannot be undone.

:::danger
Deleting a custom field permanently removes all stored values for that field. There is no way to recover them.
:::

## Custom fields are per-company

Each company in SolidInvoice has its own set of custom fields. Fields created in one company are not shared with other companies.

## Related

- [Creating a new client](./create-new-client.md)
- [Creating an invoice](../invoices/creating-an-invoice.md)
