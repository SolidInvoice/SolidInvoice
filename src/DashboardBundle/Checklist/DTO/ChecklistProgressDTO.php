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

namespace SolidInvoice\DashboardBundle\Checklist\DTO;

final readonly class ChecklistProgressDTO
{
    /**
     * @param array<ChecklistItemDTO> $items
     */
    public function __construct(
        public array $items,
        public int $completed,
        public int $total,
        public int $percentage,
        public bool $allComplete,
    ) {
    }
}
