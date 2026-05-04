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

#[Route(path: '/.well-known/agent-skills/index.json', name: 'mcp_well_known_agent_skills_index', methods: ['GET'])]
final class WellKnownAgentSkillsIndex
{
    private const SCHEMA_URL = 'https://raw.githubusercontent.com/cloudflare/agent-skills-discovery-rfc/main/schemas/v0.2.0/index.json';

    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            '$schema' => self::SCHEMA_URL,
            'skills' => [],
        ]);
    }
}
