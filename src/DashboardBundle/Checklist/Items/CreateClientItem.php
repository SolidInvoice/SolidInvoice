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

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;

final readonly class CreateClientItem implements ChecklistItemInterface
{
    public function __construct(
        private ClientRepository $clientRepository,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.create_client.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.create_client.description';
    }

    public function getIcon(): string
    {
        return 'tabler:users';
    }

    public function getRoute(): string
    {
        return '_clients_add';
    }

    public function getPriority(): int
    {
        return -300;
    }

    public function active(): bool
    {
        return true;
    }

    public function isComplete(): bool
    {
        // Company filter ensures we only count clients for the current company
        return $this->clientRepository->count([]) > 0;
    }
}
