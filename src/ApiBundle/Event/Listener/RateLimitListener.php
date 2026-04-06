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

namespace SolidInvoice\ApiBundle\Event\Listener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::REQUEST, priority: -10)]
final class RateLimitListener
{
    public function __construct(
        #[Autowire(service: 'limiter.api_global')]
        private readonly RateLimiterFactory $limiter,
        private readonly Security $security,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (! str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $rateLimitKey = $this->security->getUser()?->getUserIdentifier()
            ?? $request->getClientIp();

        if ($rateLimitKey === null) {
            // IP cannot be resolved; skip rate limiting rather than sharing a single anonymous bucket.
            return;
        }

        $limiter = $this->limiter->create($rateLimitKey);
        $limit = $limiter->consume();

        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();

        $event->setResponse(
            new JsonResponse(
                ['error' => 'Rate limit exceeded. Please retry after ' . $retryAfter . ' seconds.'],
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
                [
                    'Content-Type' => 'application/problem+json',
                    'Retry-After' => (string) max(0, $retryAfter),
                    'X-RateLimit-Limit' => (string) $limit->getLimit(),
                    'X-RateLimit-Remaining' => (string) $limit->getRemainingTokens(),
                    'X-RateLimit-Reset' => (string) $limit->getRetryAfter()->getTimestamp(),
                ]
            )
        );
    }
}
