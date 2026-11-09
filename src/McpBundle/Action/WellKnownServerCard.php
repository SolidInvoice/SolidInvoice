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

use Mcp\Capability\Registry;
use Mcp\Schema\Tool;
use Mcp\Server;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function array_map;
use function array_values;

/**
 * MCP Server Card (https://github.com/modelcontextprotocol/experimental-ext-server-card).
 *
 * The draft covers identity and remotes only; the authentication hints and the tool summary live
 * under vendor-namespaced `_meta` keys, which the draft allows for non-standard data.
 */
#[Route(path: '/.well-known/mcp/server-card.json', name: 'mcp_well_known_server_card', methods: ['GET'])]
final readonly class WellKnownServerCard
{
    /** @phpstan-ignore constructor.unusedParameter */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire(service: 'mcp.server.solidinvoice.registry')]
        private Registry $registry,
        // Building the server is what loads the tools into the registry; without it the registry is empty.
        #[Autowire(service: 'mcp.server.solidinvoice')]
        Server $server,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            '$schema' => 'https://static.modelcontextprotocol.io/schemas/v1/server-card.schema.json',
            'name' => 'co.solidinvoice/mcp',
            'version' => SolidInvoiceCoreBundle::VERSION,
            'description' => 'Create and send invoices and quotes, manage clients, and track payments in SolidInvoice.',
            'title' => SolidInvoiceCoreBundle::APP_NAME,
            'websiteUrl' => 'https://solidinvoice.co/docs/ai/mcp-server/',
            'icons' => [
                ['src' => 'https://solidinvoice.co/logo-512.png', 'mimeType' => 'image/png', 'sizes' => ['512x512']],
                ['src' => 'https://solidinvoice.co/logo-192.png', 'mimeType' => 'image/png', 'sizes' => ['192x192']],
            ],
            'remotes' => [
                [
                    'type' => 'streamable-http',
                    'url' => $this->urlGenerator->generate('_mcp_endpoint_solidinvoice', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
            ],
            '_meta' => [
                'co.solidinvoice/authentication' => [
                    'type' => 'oauth2',
                    'protectedResourceMetadata' => $this->urlGenerator->generate('mcp_well_known_protected_resource', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'scopes' => McpScope::values(),
                    'publicToolsList' => true,
                ],
                // Names, titles, descriptions and annotations only; tools/list has the full schemas.
                'co.solidinvoice/tools' => array_values(array_map(
                    static fn (Tool $tool): array => array_filter([
                        'name' => $tool->name,
                        'title' => $tool->title,
                        'description' => $tool->description,
                        'annotations' => $tool->annotations,
                    ], static fn (mixed $v): bool => $v !== null),
                    $this->registry->getTools()->references,
                )),
            ],
        ]);
    }
}
