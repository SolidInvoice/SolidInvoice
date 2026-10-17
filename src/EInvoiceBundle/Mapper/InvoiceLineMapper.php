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

namespace SolidInvoice\EInvoiceBundle\Mapper;

use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\Collection;
use Money\Currency;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;
use SolidInvoice\EInvoiceBundle\Model\InvoiceLine;
use SolidInvoice\EInvoiceBundle\Model\ItemInformation;
use SolidInvoice\EInvoiceBundle\Model\LineVatInformation;
use SolidInvoice\EInvoiceBundle\Model\PriceDetails;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Calculator\Result\LineBreakdown;
use SolidInvoice\TaxBundle\Entity\LineTax;

/**
 * Maps BG-25/BG-29/BG-30/BG-31 (one invoice line) from a {@see Line} and the matching
 * {@see LineBreakdown} the caller's single delegated
 * {@see \SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface} call already produced.
 * BT-131 is read off the breakdown, never recomputed (design §13.1).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Mapper\InvoiceLineMapperTest
 */
final readonly class InvoiceLineMapper
{
    public function __construct(
        private MinorUnitConverter $converter,
    ) {
    }

    public function map(Line $line, LineBreakdown $breakdown, Currency $currency): InvoiceLine
    {
        return new InvoiceLine(
            identifier: (string) ($line->getPosition() + 1),
            invoicedQuantity: $line->getQty()->toBigDecimal(),
            netAmount: $this->converter->toMajorUnit($breakdown->lineSubtotal, $currency),
            price: new PriceDetails(
                netPrice: $this->converter->toMajorUnit($line->getPrice(), $currency),
            ),
            vat: $this->mapVat($line->getTaxes()),
            item: new ItemInformation(
                name: $line->getName(),
                description: $line->getDescription(),
            ),
        );
    }

    /**
     * BT-151/152. A line carrying several VAT-relevant taxes is a data shape EN 16931 does not
     * allow, so this maps the first tax row by sequence and lets the validator report the rest
     * (design §5.4).
     *
     * A line with no tax at all has no basis for a category. SolidInvoice allows an untaxed
     * line (no tax rates configured is the default install), and BT-151's correct value here
     * depends on the seller's VAT registration status, which the entity model does not carry
     * until SOL-71. Mapping null lets a BT-coded validation message report it once SOL-108
     * lands, instead of guessing a category that would validate and be wrong (design §15).
     *
     * @param Collection<int, LineTax> $taxes
     */
    private function mapVat(Collection $taxes): LineVatInformation
    {
        $items = $taxes->toArray();
        usort($items, static fn (LineTax $a, LineTax $b): int => $a->getSequence() <=> $b->getSequence());

        $first = $items[0] ?? null;

        if (! $first instanceof LineTax) {
            return new LineVatInformation(categoryCode: null, rate: null);
        }

        return new LineVatInformation(
            categoryCode: VatCategoryCode::fromTaxCategory($first->getCategorySnapshot()),
            rate: BigDecimal::of($first->getRateSnapshot()),
        );
    }
}
