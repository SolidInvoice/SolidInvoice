/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

interface WebMcpTool {
    name: string;
    description: string;
    inputSchema: object;
    execute: (args: Record<string, unknown>) => Promise<unknown> | unknown;
}

interface ModelContext {
    provideContext(context: { tools: WebMcpTool[] }): void;
}

declare global {
    interface Navigator {
        modelContext?: ModelContext;
    }
}

const navigateTo = (path: string): { ok: true; navigated: string } => {
    window.location.assign(path);
    return { ok: true, navigated: path };
};

const tools: WebMcpTool[] = [
    {
        name: 'open_dashboard',
        description: 'Navigate to the SolidInvoice dashboard.',
        inputSchema: { type: 'object', properties: {}, additionalProperties: false },
        execute: () => navigateTo('/dashboard'),
    },
    {
        name: 'open_invoices',
        description: 'Navigate to the list of invoices.',
        inputSchema: { type: 'object', properties: {}, additionalProperties: false },
        execute: () => navigateTo('/invoices/'),
    },
    {
        name: 'open_recurring_invoices',
        description: 'Navigate to the list of recurring invoices.',
        inputSchema: { type: 'object', properties: {}, additionalProperties: false },
        execute: () => navigateTo('/invoices/recurring'),
    },
    {
        name: 'open_quotes',
        description: 'Navigate to the list of quotes.',
        inputSchema: { type: 'object', properties: {}, additionalProperties: false },
        execute: () => navigateTo('/quotes/'),
    },
    {
        name: 'open_clients',
        description: 'Navigate to the list of clients.',
        inputSchema: { type: 'object', properties: {}, additionalProperties: false },
        execute: () => navigateTo('/clients/'),
    },
    {
        name: 'view_client',
        description: 'Open a client by their ULID.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'string', description: 'Client ULID' },
            },
            required: ['id'],
            additionalProperties: false,
        },
        execute: ({ id }) => navigateTo(`/clients/view/${encodeURIComponent(String(id))}`),
    },
    {
        name: 'view_invoice',
        description: 'Open an invoice by its ULID.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'string', description: 'Invoice ULID' },
            },
            required: ['id'],
            additionalProperties: false,
        },
        execute: ({ id }) => navigateTo(`/invoices/view/${encodeURIComponent(String(id))}`),
    },
    {
        name: 'view_quote',
        description: 'Open a quote by its ULID.',
        inputSchema: {
            type: 'object',
            properties: {
                id: { type: 'string', description: 'Quote ULID' },
            },
            required: ['id'],
            additionalProperties: false,
        },
        execute: ({ id }) => navigateTo(`/quotes/view/${encodeURIComponent(String(id))}`),
    },
    {
        name: 'create_invoice_for_client',
        description: 'Start creating an invoice for an existing client (by ULID).',
        inputSchema: {
            type: 'object',
            properties: {
                client: { type: 'string', description: 'Client ULID' },
            },
            required: ['client'],
            additionalProperties: false,
        },
        execute: ({ client }) => navigateTo(`/invoices/create/${encodeURIComponent(String(client))}`),
    },
    {
        name: 'create_quote_for_client',
        description: 'Start creating a quote for an existing client (by ULID).',
        inputSchema: {
            type: 'object',
            properties: {
                client: { type: 'string', description: 'Client ULID' },
            },
            required: ['client'],
            additionalProperties: false,
        },
        execute: ({ client }) => navigateTo(`/quotes/create/${encodeURIComponent(String(client))}`),
    },
    {
        name: 'add_client',
        description: 'Open the form to add a new client.',
        inputSchema: { type: 'object', properties: {}, additionalProperties: false },
        execute: () => navigateTo('/clients/add'),
    },
];

export const registerWebMcpTools = (): void => {
    if (typeof navigator === 'undefined' || ! navigator.modelContext) {
        return;
    }

    navigator.modelContext.provideContext({ tools });
};

registerWebMcpTools();
