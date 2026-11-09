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

namespace SolidInvoice\ProductBundle\Twig\Components;

use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use function array_values;
use function is_array;
use function is_string;

/**
 * The "+ Add Product" picker of {@see \SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice},
 * {@see \SolidInvoice\InvoiceBundle\Twig\Components\CreateRecurringInvoice} and
 * {@see \SolidInvoice\QuoteBundle\Twig\Components\CreateQuote}.
 *
 * The using component provides `$featureGate`, `$productRepository` and `$taxRepository`.
 *
 * @see \SolidInvoice\ProductBundle\Tests\Twig\Components\AddProductLineTest
 */
trait AddsProductLines
{
    /**
     * Appends a line filled from the picked product, then empties the picker.
     *
     * The line copies the product's values and keeps only the link: later edits to the
     * product never touch it. The id is looked up through the company-filtered
     * repository, so a foreign or unknown id adds nothing.
     */
    #[LiveAction]
    public function addProductLine(): void
    {
        $id = $this->formValues['addProduct'] ?? '';
        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            return;
        }

        $this->formValues['addProduct'] = '';

        if (! is_string($id) || ! Ulid::isValid($id, Ulid::FORMAT_BASE_32)) {
            return;
        }

        $product = $this->productRepository->find(Ulid::fromString($id));

        if (! $product instanceof Product) {
            return;
        }

        $lines = $this->formValues['lines'] ?? [];
        $lines = is_array($lines) ? array_values($lines) : [];

        $line = [
            'name' => $product->getName(),
            'description' => $product->getDescription() ?? '',
            'price' => (string) $product->getPrice(),
            'qty' => (string) $product->getQuantity(),
            'product' => (string) $product->getId(),
        ];

        // Without configured tax rates the form has no `taxes` field to carry it.
        if ($product->getTax() instanceof Tax && $this->taxRepository->taxRatesConfigured()) {
            $line['taxes'] = [['tax' => (string) $product->getTax()->getId(), 'sequence' => 0]];
        }

        $lines[] = $line;
        $this->formValues['lines'] = $lines;
    }
}
