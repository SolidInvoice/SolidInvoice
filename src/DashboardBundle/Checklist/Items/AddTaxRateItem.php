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

namespace SolidInvoice\DashboardBundle\Checklist\Items;

use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\TaxBundle\Repository\TaxRepository;

final readonly class AddTaxRateItem implements ChecklistItemInterface
{
    public function __construct(
        private TaxRepository $taxRepository,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.add_tax_rate.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.add_tax_rate.description';
    }

    public function getIcon(): string
    {
        return 'tabler:receipt-tax';
    }

    public function getRoute(): string
    {
        return '_tax_rates';
    }

    public function getPriority(): int
    {
        return -400;
    }

    public function active(): bool
    {
        return false;
    }

    public function isComplete(): bool
    {
        // Company filter ensures we only count tax rates for the current company
        return $this->taxRepository->count([]) > 0;
    }
}
