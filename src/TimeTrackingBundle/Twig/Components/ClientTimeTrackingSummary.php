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

namespace SolidInvoice\TimeTrackingBundle\Twig\Components;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\TimeTrackingBundle\Repository\TimeEntryRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ClientTimeTrackingSummary
{
    public Client $client;

    public function __construct(
        private readonly TimeEntryRepository $timeEntryRepository,
    ) {
    }

    public function getPendingCount(): int
    {
        return $this->timeEntryRepository->getPendingCountForClient($this->client);
    }

    public function getTotalPendingDuration(): int
    {
        return $this->timeEntryRepository->getTotalPendingDuration($this->client);
    }

    public function formatDuration(int $seconds): string
    {
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);

        return sprintf('%dh %02dm', $hours, $minutes);
    }
}
