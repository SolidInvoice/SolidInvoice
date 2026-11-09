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

use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientOutput;

final readonly class ClientSummaryStatsOutput
{
    /**
     * @param list<StatusCountOutput> $counts_by_status
     * @param list<AmountOutput>      $total_invoiced
     * @param list<AmountOutput>      $outstanding
     * @param list<AmountOutput>      $total_paid
     */
    public function __construct(
        public ClientOutput $client,
        public array $counts_by_status,
        public array $total_invoiced,
        public array $outstanding,
        public array $total_paid,
    ) {
    }
}
