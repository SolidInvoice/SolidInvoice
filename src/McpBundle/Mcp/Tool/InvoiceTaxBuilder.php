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

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Exception\ToolCallException;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxDirection;

/**
 * Converts the array shape MCP tools receive for invoice-level taxes
 * (withholding/surcharge/informational rows) into {@see InvoiceTax} entities.
 */
final readonly class InvoiceTaxBuilder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $payload
     */
    public function attach(Invoice|Quote $document, array $payload): void
    {
        foreach (array_values($payload) as $index => $data) {
            if (! \is_array($data)) {
                throw new ToolCallException(sprintf('invoice_taxes[%d] must be an object.', $index));
            }

            $taxId = $data['tax_id'] ?? null;

            if (! \is_string($taxId) || $taxId === '') {
                throw new ToolCallException(sprintf('invoice_taxes[%d].tax_id is required.', $index));
            }

            $tax = $this->entityManager
                ->getRepository(Tax::class)
                ->find(UlidParser::parse($taxId, sprintf('invoice_taxes[%d].tax_id', $index)));

            if (! $tax instanceof Tax) {
                throw new ToolCallException(sprintf('invoice_taxes[%d] tax %s not found.', $index, $taxId));
            }

            $direction = $this->resolveDirection($data['direction'] ?? null, $index);

            $invoiceTax = new InvoiceTax();
            $invoiceTax->snapshotFrom($tax);
            $invoiceTax->setDirection($direction);
            $invoiceTax->setSequence((int) ($data['sequence'] ?? $index));

            $note = $data['note'] ?? null;
            if (\is_string($note) && $note !== '') {
                $invoiceTax->setNote($note);
            }

            $document->addInvoiceTax($invoiceTax);
        }
    }

    private function resolveDirection(mixed $value, int $index): TaxDirection
    {
        if ($value === null || $value === '') {
            return TaxDirection::Additive;
        }

        if (! \is_string($value)) {
            throw new ToolCallException(sprintf('invoice_taxes[%d].direction must be a string.', $index));
        }

        $direction = TaxDirection::tryFrom($value);

        if ($direction === null) {
            throw new ToolCallException(sprintf(
                'invoice_taxes[%d].direction "%s" is invalid. Expected one of: %s.',
                $index,
                $value,
                implode(', ', array_map(static fn (TaxDirection $d): string => $d->value, TaxDirection::cases())),
            ));
        }

        return $direction;
    }
}
