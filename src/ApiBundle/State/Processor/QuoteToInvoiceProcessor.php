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
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** @implements ProcessorInterface<Quote, Invoice> */
final class QuoteToInvoiceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly InvoiceManager $invoiceManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Invoice
    {
        assert($data instanceof Quote);

        if ($data->getInvoice() !== null) {
            throw new UnprocessableEntityHttpException('This quote has already been converted to an invoice.');
        }

        $invoice = $this->invoiceManager->createFromQuote($data);

        return $this->invoiceManager->create($invoice);
    }
}
