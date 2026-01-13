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

final readonly class ChecklistItemDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public string $icon,
        public string $route,
        public bool $completed,
    ) {
    }
}
