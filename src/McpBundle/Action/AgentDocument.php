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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the Markdown documents in Resources/agent/ with the request's base URL filled in.
 * The agent skills index hashes the same rendered output, so the digest always matches.
 */
final class AgentDocument
{
    public const string SKILL = 'solidinvoice-mcp/SKILL.md';

    public const string AUTH = 'auth.md';

    #[Route(path: '/auth.md', name: 'mcp_auth_md', defaults: ['document' => self::AUTH], methods: ['GET'])]
    #[Route(path: '/.well-known/agent-skills/solidinvoice-mcp/SKILL.md', name: 'mcp_well_known_agent_skill', defaults: ['document' => self::SKILL], methods: ['GET'])]
    public function __invoke(Request $request, string $document): Response
    {
        return new Response(self::render($request, $document), Response::HTTP_OK, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public static function render(Request $request, string $document): string
    {
        $base = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

        return str_replace('%base_url%', $base, (string) file_get_contents(__DIR__ . '/../Resources/agent/' . $document));
    }
}
