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

namespace SolidInvoice\ApiBundle\Action;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/.well-known/api-catalog', name: 'api_well_known_catalog', methods: ['GET'])]
final class WellKnownApiCatalog
{
    public function __invoke(Request $request): JsonResponse
    {
        $base = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

        $response = new JsonResponse([
            'linkset' => [
                [
                    'anchor' => $base . '/api',
                    'service-desc' => [
                        [
                            'href' => $base . '/api/docs.jsonld',
                            'type' => 'application/ld+json',
                        ],
                        [
                            'href' => $base . '/api/docs.json',
                            'type' => 'application/vnd.openapi+json;version=3.1',
                        ],
                    ],
                    'service-doc' => [
                        [
                            'href' => $base . '/api/docs',
                            'type' => 'text/html',
                        ],
                    ],
                ],
            ],
        ]);

        $response->headers->set('Content-Type', 'application/linkset+json');

        return $response;
    }
}
