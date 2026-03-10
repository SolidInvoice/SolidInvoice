<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Config\MeilisearchConfig;

return static function (MeilisearchConfig $config): void {
    $config
        ->url('%env(SOLIDINVOICE_MEILISEARCH_URL)%')
        ->apiKey('%env(SOLIDINVOICE_MEILISEARCH_API_KEY)%')
        ->prefix('%env(SOLIDINVOICE_MEILISEARCH_PREFIX)%');

    $config->indices()
        ->name('clients')
        ->class(Client::class)
        ->enableSerializerGroups(true)
        ->serializerGroups(['searchable'])
        ->settings([
            'filterableAttributes' => ['companyId', 'status'],
            'sortableAttributes' => ['name'],
        ]);

    $config->indices()
        ->name('contacts')
        ->class(Contact::class)
        ->enableSerializerGroups(true)
        ->serializerGroups(['searchable'])
        ->settings([
            'filterableAttributes' => ['companyId', 'clientId'],
        ]);

    $config->indices()
        ->name('invoices')
        ->class(Invoice::class)
        ->enableSerializerGroups(true)
        ->serializerGroups(['searchable'])
        ->settings([
            'filterableAttributes' => ['companyId', 'status', 'total', 'client.name', 'created'],
            'sortableAttributes' => ['total', 'created'],
        ]);

    $config->indices()
        ->name('recurring_invoices')
        ->class(RecurringInvoice::class)
        ->enableSerializerGroups(true)
        ->serializerGroups(['searchable'])
        ->settings([
            'filterableAttributes' => ['companyId', 'status', 'total', 'client.name'],
            'sortableAttributes' => ['total', 'created'],
        ]);

    $config->indices()
        ->name('quotes')
        ->class(Quote::class)
        ->enableSerializerGroups(true)
        ->serializerGroups(['searchable'])
        ->settings([
            'filterableAttributes' => ['companyId', 'status', 'total', 'client.name', 'created'],
            'sortableAttributes' => ['total', 'created'],
        ]);

    $config->indices()
        ->name('payments')
        ->class(Payment::class)
        ->enableSerializerGroups(true)
        ->serializerGroups(['searchable'])
        ->settings([
            'filterableAttributes' => ['companyId', 'status', 'client.name', 'total'],
            'sortableAttributes' => ['total'],
        ]);
};
