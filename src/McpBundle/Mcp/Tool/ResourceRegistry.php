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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * Maps short resource identifiers used by MCP tools to their Doctrine entity
 * classes. Tools pass a name like "invoice" or "client"; this service resolves
 * the class, shielding tool callers from internal namespaces.
 */
final class ResourceRegistry
{
    /**
     * @var array<string, class-string>
     */
    private const array RESOURCES = [
        'invoice' => Invoice::class,
        'recurring_invoice' => RecurringInvoice::class,
        'quote' => Quote::class,
        'client' => Client::class,
        'contact' => Contact::class,
        'payment' => Payment::class,
        'payment_method' => PaymentMethod::class,
        'tax' => Tax::class,
    ];

    /**
     * @return class-string
     *
     * @throws ToolCallException
     */
    public function resolve(string $resource): string
    {
        if (! isset(self::RESOURCES[$resource])) {
            throw new ToolCallException(sprintf(
                'Unknown resource "%s". Supported resources: %s.',
                $resource,
                implode(', ', array_keys(self::RESOURCES)),
            ));
        }

        return self::RESOURCES[$resource];
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_keys(self::RESOURCES);
    }
}
