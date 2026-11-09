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

namespace SolidInvoice\SaasBundle\Action\OneTap;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use function max;

trait OneTapRateLimit
{
    /**
     * Consume one token from the limiter (keyed by client IP) and return a 429
     * response when the request is not accepted, or null when it may proceed.
     */
    private function enforceRateLimit(?RateLimiterFactory $limiter, Request $request): ?JsonResponse
    {
        if (! $limiter instanceof RateLimiterFactory) {
            return null;
        }

        $limit = $limiter->create($request->getClientIp() ?? 'unknown')->consume();

        if ($limit->isAccepted()) {
            return null;
        }

        $retryAfter = $limit->getRetryAfter();
        $retryAfterSeconds = max(0, $retryAfter->getTimestamp() - Carbon::now()->getTimestamp());

        return new JsonResponse(
            ['error' => 'rate_limited', 'error_description' => 'Too many requests. Please try again later.'],
            Response::HTTP_TOO_MANY_REQUESTS,
            [
                'Retry-After' => (string) $retryAfterSeconds,
                'X-RateLimit-Reset' => (string) $retryAfter->getTimestamp(),
            ],
        );
    }
}
