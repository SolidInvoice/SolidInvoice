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

namespace SolidInvoice\TaxBundle\Twig\Extension;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Override;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Calculator\Result\CalculationResult;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use Symfony\Component\Uid\Ulid;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WeakMap;

final class TaxBreakdownExtension extends AbstractExtension
{
    /**
     * @var WeakMap<BaseInvoice|Quote, CalculationResult>
     */
    private WeakMap $cache;

    public function __construct(
        private readonly TaxIdentifierRepository $taxIdentifierRepository,
        private readonly CompanySelector $companySelector,
        private readonly TaxCalculatorInterface $taxCalculator,
    ) {
        $this->cache = new WeakMap();
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('tax_identifiers', $this->taxIdentifiers(...)),
            new TwigFunction('tax_breakdown', $this->taxBreakdown(...)),
            new TwigFunction('payable_amount', $this->payableAmount(...)),
        ];
    }

    /**
     * Returns an ordered list of TaxIdentifier entities for the given owner.
     *
     * @return list<TaxIdentifier>
     */
    public function taxIdentifiers(Client|Company|null $owner = null): array
    {
        $identifiers = $owner instanceof Client
            ? $this->forClient($owner)
            : $this->forCompany($owner);

        usort($identifiers, static function (TaxIdentifier $a, TaxIdentifier $b): int {
            if ($a->isPrimary() !== $b->isPrimary()) {
                return $a->isPrimary() ? -1 : 1;
            }

            return strcasecmp((string) $a->getLabel(), (string) $b->getLabel());
        });

        return $identifiers;
    }

    /**
     * Compute or retrieve a cached {@see CalculationResult} for the document, then
     * shape it into a structure templates can iterate without reaching into the
     * domain objects.
     *
     * Shape:
     *  - subTotal:       BigNumber
     *  - lineTaxRows:    list<TaxSummaryRow>    line-level taxes (Additive)
     *  - additiveRows:   list<TaxSummaryRow>    invoice-level Additive taxes
     *  - deductiveRows:  list<TaxSummaryRow>    invoice-level Deductive (withholding)
     *  - informationalRows: list<TaxSummaryRow> invoice-level Informational rows
     *  - total:          BigNumber
     *  - totalLineTax:   BigNumber
     *  - totalWithholding: BigNumber
     *  - amountPayable:  BigNumber
     *
     * @return array<string, mixed>
     */
    public function taxBreakdown(BaseInvoice|Quote $document): array
    {
        $result = $this->cache[$document] ?? null;

        if ($result === null) {
            $result = $this->taxCalculator->calculate($document);
            $this->cache[$document] = $result;
        }

        $lineTaxRows = [];
        $additiveRows = [];
        $deductiveRows = [];
        $informationalRows = [];

        foreach ($result->summaryRows as $row) {
            // Line-level rows are always Additive with note=null.
            // Invoice-level rows can be in any direction.
            if ($row->direction === TaxDirection::Deductive) {
                $deductiveRows[] = $row;
                continue;
            }

            if ($row->direction === TaxDirection::Informational) {
                $informationalRows[] = $row;
                continue;
            }

            // Additive: split between line-level (note=null) and invoice-level by sequence.
            // Heuristic: line-level rows are merged across all lines and carry note=null;
            // invoice-level Additive rows always come from InvoiceTax (note may still be null).
            // Use the invoice-level breakdown's own row list to decide which is which.
            if ($this->isInvoiceLevelRow($row, $result)) {
                $additiveRows[] = $row;
            } else {
                $lineTaxRows[] = $row;
            }
        }

        return [
            'subTotal' => $result->subTotal,
            'lineTaxRows' => $lineTaxRows,
            'additiveRows' => $additiveRows,
            'deductiveRows' => $deductiveRows,
            'informationalRows' => $informationalRows,
            'totalLineTax' => $result->totalLineTax,
            'totalAdditive' => $result->invoiceLevelBreakdown->totalInvoiceLevelTax,
            'totalWithholding' => $result->totalWithholding,
            'total' => $result->total,
            'amountPayable' => $result->amountPayable,
        ];
    }

    /**
     * Returns the persisted payable amount on the document. Falls back to
     * `total - withholding` recomputed when the document has not been saved
     * since US-008 (e.g., a draft never run through TotalCalculator).
     */
    public function payableAmount(BaseInvoice|Quote $document): BigNumber
    {
        $persisted = $document->getPayableAmount();

        if (! $persisted->isZero()) {
            return $persisted;
        }

        return BigDecimal::of($document->getTotal())->minus(BigDecimal::of($document->getWithholdingAmount()));
    }

    private function isInvoiceLevelRow(TaxSummaryRow $row, CalculationResult $result): bool
    {
        return array_any($result->invoiceLevelBreakdown->taxRows, fn ($candidate) => $candidate->name === $row->name
        && $candidate->rate === $row->rate
        && $candidate->direction === $row->direction
        && ($candidate->note ?? '') === ($row->note ?? ''));
    }

    /**
     * @return list<TaxIdentifier>
     */
    private function forClient(Client $client): array
    {
        return array_values($client->getTaxIdentifiers()->toArray());
    }

    /**
     * @return list<TaxIdentifier>
     */
    private function forCompany(?Company $company): array
    {
        $companyId = $company?->getId() ?? $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return [];
        }

        return $this->taxIdentifierRepository->findCompanyIdentifiers($companyId);
    }
}
