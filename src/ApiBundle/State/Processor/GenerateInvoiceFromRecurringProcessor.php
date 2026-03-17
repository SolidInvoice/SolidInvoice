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
use DateTimeImmutable;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<RecurringInvoice, Invoice> */
final class GenerateInvoiceFromRecurringProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly InvoiceManager $invoiceManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Invoice
    {
        assert($data instanceof RecurringInvoice);

        if ($data->hasInvoiceForDay(new DateTimeImmutable())) {
            throw new UnprocessableEntityHttpException('An invoice has already been generated for this recurring invoice today.');
        }

        $invoice = $this->invoiceManager->createFromRecurring($data);

        return $this->invoiceManager->create($invoice);
    }
}
