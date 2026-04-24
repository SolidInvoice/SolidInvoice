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

namespace SolidInvoice\McpBundle\Mcp\Listener;

use Psr\Log\LoggerInterface;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Logs every MCP request to the monolog "mcp" channel with the tool name
 * (parsed from the JSON-RPC body), user, company, scopes, response status,
 * and latency.
 */
final class McpAuditListener
{
    /**
     * @var array<string, array{start: float, method: string|null, tool: string|null}>
     */
    private array $pending = [];

    public function __construct(
        #[Autowire(service: 'monolog.logger.mcp')]
        private readonly LoggerInterface $logger,
        private readonly Security $security,
    ) {
    }

    #[AsEventListener(event: RequestEvent::class, priority: 16)]
    public function onRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (! str_starts_with($request->getPathInfo(), '/_mcp')) {
            return;
        }

        $method = null;
        $toolName = null;

        $body = $request->getContent() ?: null;

        if ($body !== null) {
            $parsed = json_decode($body, true);

            if (\is_array($parsed)) {
                $method = \is_string($parsed['method'] ?? null) ? $parsed['method'] : null;

                if ($method === 'tools/call' && \is_array($parsed['params'] ?? null)) {
                    $toolName = \is_string($parsed['params']['name'] ?? null) ? $parsed['params']['name'] : null;
                }
            }
        }

        $this->pending[spl_object_hash($request)] = [
            'start' => microtime(true),
            'method' => $method,
            'tool' => $toolName,
        ];
    }

    #[AsEventListener(event: ExceptionEvent::class, priority: -16)]
    public function onException(ExceptionEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        unset($this->pending[spl_object_hash($event->getRequest())]);
    }

    #[AsEventListener(event: ResponseEvent::class, priority: -16)]
    public function onResponse(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $key = spl_object_hash($request);

        if (! isset($this->pending[$key])) {
            return;
        }

        $state = $this->pending[$key];
        unset($this->pending[$key]);

        $latencyMs = (int) round((microtime(true) - $state['start']) * 1000);

        $method = $state['method'];
        $toolName = $state['tool'];

        $user = $this->security->getUser();

        $this->logger->info('mcp.request', [
            'method' => $method,
            'tool' => $toolName,
            'status' => $event->getResponse()->getStatusCode(),
            'latency_ms' => $latencyMs,
            'user_id' => $user instanceof User ? $user->getId()?->toRfc4122() : null,
            'company_id' => $request->attributes->get(McpOAuthAuthenticator::ATTR_COMPANY_ID),
            'scopes' => $request->attributes->get(McpOAuthAuthenticator::ATTR_SCOPES, []),
            'access_token_id' => $request->attributes->get(McpOAuthAuthenticator::ATTR_ACCESS_TOKEN_ID),
            'ip' => $request->getClientIp(),
        ]);
    }
}
