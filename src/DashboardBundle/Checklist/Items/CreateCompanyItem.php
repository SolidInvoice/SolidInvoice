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

/**
 * Always complete: the dashboard is only reachable once a company exists. It
 * opens the checklist with a step already done (endowed progress).
 */
final readonly class CreateCompanyItem implements ChecklistItemInterface
{
    public function getName(): string
    {
        return 'dashboard.checklist.create_company.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.create_company.description';
    }

    public function getIcon(): string
    {
        return 'tabler:building';
    }

    public function getRoute(): string
    {
        return '_settings';
    }

    public function getPriority(): int
    {
        return -200;
    }

    public function active(): bool
    {
        return true;
    }

    public function isComplete(): bool
    {
        return true;
    }
}
