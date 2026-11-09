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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Entity\Invoice;

/** @implements ProcessorInterface<Invoice, Invoice> */
final readonly class InvoiceCloneProcessor implements ProcessorInterface
{
    public function __construct(
        private InvoiceCloner $cloner,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Invoice
    {
        assert($data instanceof Invoice);

        // InvoiceCloner persists an Invoice clone itself (through InvoiceManager::create()).
        $clone = $this->cloner->clone($data);
        assert($clone instanceof Invoice);

        return $clone;
    }
}
