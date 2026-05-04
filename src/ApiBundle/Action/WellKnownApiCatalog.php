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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/.well-known/api-catalog', name: 'api_well_known_catalog', methods: ['GET'])]
final class WellKnownApiCatalog
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $apiBase = $this->urlGenerator->generate('api_entrypoint', ['index' => 'index'], UrlGeneratorInterface::ABSOLUTE_URL);
        $apiBase = rtrim(preg_replace('#/index$#', '', $apiBase) ?? $apiBase, '/');

        $response = new JsonResponse([
            'linkset' => [
                [
                    'anchor' => $apiBase,
                    'service-desc' => [
                        [
                            'href' => $this->urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'type' => 'application/ld+json',
                        ],
                        [
                            'href' => $this->urlGenerator->generate('api_doc', ['_format' => 'json'], UrlGeneratorInterface::ABSOLUTE_URL),
                            'type' => 'application/vnd.openapi+json;version=3.1',
                        ],
                    ],
                    'service-doc' => [
                        [
                            'href' => $this->urlGenerator->generate('api_doc', ['_format' => 'html'], UrlGeneratorInterface::ABSOLUTE_URL),
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
