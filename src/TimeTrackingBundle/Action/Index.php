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

namespace SolidInvoice\TimeTrackingBundle\Action;

use SolidInvoice\TimeTrackingBundle\Enum\TimeEntryStatus;
use SolidInvoice\TimeTrackingBundle\Repository\TimeEntryRepository;
use Symfony\Bridge\Twig\Attribute\Template;

final readonly class Index
{
    public function __construct(
        private TimeEntryRepository $timeEntryRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[Template('@SolidInvoiceTimeTracking/Default/index.html.twig')]
    public function __invoke(): array
    {
        $pendingCount = $this->timeEntryRepository->count(['status' => TimeEntryStatus::Pending]);
        $invoicedCount = $this->timeEntryRepository->count(['status' => TimeEntryStatus::Invoiced]);

        return [
            'pendingCount' => $pendingCount,
            'invoicedCount' => $invoicedCount,
        ];
    }
}
