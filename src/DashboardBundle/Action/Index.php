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

namespace SolidInvoice\DashboardBundle\Action;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

final readonly class Index
{
    public function __construct(ClientRepository $clientRepository)
    {
        dd(
            $clientRepository->find(Ulid::fromString('01KSFZJXVKRXXQE0DDRTXPKHE3')),
            $clientRepository->find(Ulid::fromString('01KSFZG96P6AHPR78BGQV1ZR1S')),
        );
    }

    /**
     * @return array{}
     */
    #[Template('@SolidInvoiceDashboard/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        return [];
    }
}
