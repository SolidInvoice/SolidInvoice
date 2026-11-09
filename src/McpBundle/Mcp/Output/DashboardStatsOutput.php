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

namespace SolidInvoice\McpBundle\Mcp\Output;

final readonly class DashboardStatsOutput
{
    /**
     * @param list<AmountOutput>      $outstanding
     * @param list<AmountOutput>      $overdue
     * @param list<StatusCountOutput> $counts_by_status
     */
    public function __construct(
        public array $outstanding,
        public array $overdue,
        public array $counts_by_status,
        public int $total_invoices,
        public int $total_clients,
    ) {
    }
}
