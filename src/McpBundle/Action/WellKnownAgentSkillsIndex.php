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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/.well-known/agent-skills/index.json', name: 'mcp_well_known_agent_skills_index', methods: ['GET'])]
final readonly class WellKnownAgentSkillsIndex
{
    private const string SCHEMA_URL = 'https://schemas.agentskills.io/discovery/0.2.0/schema.json';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            '$schema' => self::SCHEMA_URL,
            'skills' => [
                [
                    'name' => 'solidinvoice-mcp',
                    'type' => 'skill-md',
                    'description' => 'Connect to a SolidInvoice account over MCP to read and manage invoices, quotes, clients, contacts, payments and taxes.',
                    'url' => $this->urlGenerator->generate('mcp_well_known_agent_skill', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'digest' => 'sha256:' . hash('sha256', AgentDocument::render($request, AgentDocument::SKILL)),
                ],
            ],
        ]);
    }
}
