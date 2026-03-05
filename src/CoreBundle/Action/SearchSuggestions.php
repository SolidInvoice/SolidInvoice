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

namespace SolidInvoice\CoreBundle\Action;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class SearchSuggestions
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $qualifier = (string) $request->query->get('qualifier', '');
        $partial = (string) $request->query->get('q', '');

        if ($this->companySelector->getCompany() === null) {
            return new JsonResponse([]);
        }

        $values = match ($qualifier) {
            'client' => $this->clientRepository->createQueryBuilder('c')
                ->select('c.name')
                ->where('c.name LIKE :partial')
                ->setParameter('partial', '%' . $partial . '%')
                ->setMaxResults(10)
                ->getQuery()
                ->getSingleColumnResult(),
            default => [],
        };

        return new JsonResponse($values);
    }
}
