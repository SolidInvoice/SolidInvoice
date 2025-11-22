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

namespace SolidInvoice\ClientBundle\Action;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Request;

final class Index
{
    public function __construct(
        private readonly ClientRepository $clientRepository
    ) {
    }

    #[\Symfony\Bridge\Twig\Attribute\Template('@SolidInvoiceClient/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        return [
            'count' => $this->clientRepository->count([]),
        ];
    }
}
