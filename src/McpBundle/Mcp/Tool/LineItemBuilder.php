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

use Brick\Math\BigDecimal;
use Doctrine\ORM\EntityManagerInterface;
use Mcp\Exception\ToolCallException;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * Converts the array shape tools receive for line items + discount into
 * domain objects that Invoice / Quote / RecurringInvoice can consume.
 */
final class LineItemBuilder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $lines
     *
     * @return list<InvoiceLine>
     */
    public function buildInvoiceLines(array $lines): array
    {
        return $this->buildLines($lines, static fn (): InvoiceLine => new InvoiceLine());
    }

    /**
     * @param list<array<string, mixed>> $lines
     *
     * @return list<QuoteLine>
     */
    public function buildQuoteLines(array $lines): array
    {
        return $this->buildLines($lines, static fn (): QuoteLine => new QuoteLine());
    }

    /**
     * @param list<array<string, mixed>> $lines
     *
     * @return list<RecurringInvoiceLine>
     */
    public function buildRecurringInvoiceLines(array $lines): array
    {
        return $this->buildLines($lines, static fn (): RecurringInvoiceLine => new RecurringInvoiceLine());
    }

    /**
     * @param string|null     $type  "percentage" or "money" (or null for no discount)
     * @param int|float|null  $value Numeric discount value (required if $type is set)
     */
    public function buildDiscount(?string $type, int|float|null $value): Discount
    {
        $discount = new Discount();

        if ($type === null) {
            return $discount;
        }

        if ($value === null) {
            throw new ToolCallException('discount_value is required when discount_type is set.');
        }

        if ($type === Discount::TYPE_PERCENTAGE) {
            $discount->setType(Discount::TYPE_PERCENTAGE);
            $discount->setValuePercentage((float) $value);

            return $discount;
        }

        if ($type === Discount::TYPE_MONEY) {
            $discount->setType(Discount::TYPE_MONEY);

            try {
                $discount->setValueMoney(BigDecimal::of((string) $value));
            } catch (\Throwable) {
                throw new ToolCallException(sprintf('Invalid discount_value: %s', (string) $value));
            }

            return $discount;
        }

        throw new ToolCallException(sprintf(
            'Invalid discount_type "%s". Expected "%s" or "%s".',
            $type,
            Discount::TYPE_PERCENTAGE,
            Discount::TYPE_MONEY,
        ));
    }

    /**
     * @template T of InvoiceLine|QuoteLine|RecurringInvoiceLine
     *
     * @param list<array<string, mixed>> $lines
     * @param callable(): T              $factory
     *
     * @return list<T>
     */
    private function buildLines(array $lines, callable $factory): array
    {
        if ($lines === []) {
            throw new ToolCallException('At least one line item is required.');
        }

        $built = [];

        foreach ($lines as $index => $data) {
            if (! \is_array($data)) {
                throw new ToolCallException(sprintf('Line item #%d must be an object.', $index));
            }

            $description = $data['description'] ?? null;
            $price = $data['price'] ?? null;
            $qty = $data['qty'] ?? ($data['quantity'] ?? null);

            if (! \is_string($description) || $description === '') {
                throw new ToolCallException(sprintf('Line item #%d requires a non-empty "description".', $index));
            }

            if ($price === null) {
                throw new ToolCallException(sprintf('Line item #%d requires a "price" (in the minor unit, e.g. cents).', $index));
            }

            if ($qty === null) {
                $qty = 1;
            }

            $line = $factory();
            $line->setDescription($description);

            try {
                $line->setPrice(BigDecimal::of((string) $price));
            } catch (\Throwable) {
                throw new ToolCallException(sprintf('Line item #%d has an invalid "price": %s', $index, (string) $price));
            }

            $line->setQty((float) $qty);

            $taxId = $data['tax_id'] ?? null;

            if (\is_string($taxId) && $taxId !== '') {
                $tax = $this->entityManager
                    ->getRepository(Tax::class)
                    ->find(UlidParser::parse($taxId, sprintf('line[%d].tax_id', $index)));

                if (! $tax instanceof Tax) {
                    throw new ToolCallException(sprintf('Line item #%d tax %s not found.', $index, $taxId));
                }

                $line->setTax($tax);
            }

            $built[] = $line;
        }

        return $built;
    }
}
