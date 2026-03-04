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

use SolidInvoice\CoreBundle\Search\MultiSearchService;
use SolidInvoice\CoreBundle\Search\SearchResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class Search
{
    public function __construct(
        private readonly MultiSearchService $searchService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $results = $this->searchService->search($query);

        $serialized = [];
        foreach ($results as $indexName => $typeResults) {
            $serialized[$indexName] = array_map(
                static fn (SearchResult $r) => (array) $r,
                $typeResults,
            );
        }

        return new JsonResponse($serialized);
    }
}
