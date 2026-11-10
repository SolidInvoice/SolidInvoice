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

namespace SolidInvoice\McpBundle\Mcp\Output\Entity;

use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;

final readonly class RecurringScheduleOutput
{
    /**
     * @param list<int> $days
     */
    public function __construct(
        public ScheduleRecurringType $type,
        public array $days,
        public ScheduleEndType $end_type,
        public ?string $end_date,
        public ?int $end_occurrence,
    ) {
    }
}
