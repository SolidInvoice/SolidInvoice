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

namespace SolidInvoice\McpBundle\Action;

use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Agentic Resource Discovery manifest (https://agenticresourcediscovery.org/).
 */
#[Route(path: '/.well-known/ai-catalog.json', name: 'mcp_well_known_ai_catalog', methods: ['GET'])]
final readonly class WellKnownAiCatalog
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $host = $request->getHost();

        $entry = fn (string $name, string $displayName, string $type, string $route, array $params, array $queries): array => [
            'identifier' => sprintf('urn:air:%s:solidinvoice:%s', $host, $name),
            'displayName' => $displayName,
            'type' => $type,
            'url' => $this->urlGenerator->generate($route, $params, UrlGeneratorInterface::ABSOLUTE_URL),
            'representativeQueries' => $queries,
        ];

        return new JsonResponse(
            [
                'specVersion' => '1.0',
                'host' => [
                    'displayName' => SolidInvoiceCoreBundle::APP_NAME,
                    'identifier' => 'did:web:' . $host,
                ],
                'entries' => [
                    $entry('mcp', 'SolidInvoice MCP server', 'application/mcp-server-card+json', 'mcp_well_known_server_card', [], [
                        'list my unpaid invoices',
                        'create a new client in my invoicing app',
                        'which quotes are waiting for approval',
                    ]),
                    $entry('rest-api', 'SolidInvoice REST API', 'application/vnd.openapi+json', 'api_doc', ['_format' => 'json'], [
                        'invoicing API to create and send invoices',
                        'API for managing clients and quotes',
                    ]),
                    $entry('skills', 'SolidInvoice agent skills', 'application/json', 'mcp_well_known_agent_skills_index', [], [
                        'how do I connect an agent to SolidInvoice',
                        'SolidInvoice agent instructions',
                    ]),
                ],
            ],
            JsonResponse::HTTP_OK,
            ['Access-Control-Allow-Origin' => '*'],
        );
    }
}
