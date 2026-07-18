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

use SolidInvoice\SaasBundle\Security\OneTap\NonceStore;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Issues a single-use nonce for the Google One Tap widget to embed in the ID
 * token it requests from Google. Called cross-origin by the marketing site.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Action\OneTap\IssueNonceActionTest
 */
final readonly class IssueNonceAction
{
    use OneTapRateLimit;

    public function __construct(
        private ToggleInterface $toggle,
        private NonceStore $nonceStore,
        #[Autowire(service: 'limiter.one_tap_nonce')]
        private ?RateLimiterFactory $limiter = null,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->toggle->isActive('google_one_tap')) {
            throw new NotFoundHttpException();
        }

        if (($limited = $this->enforceRateLimit($this->limiter, $request)) instanceof JsonResponse) {
            return $limited;
        }

        return new JsonResponse([
            'nonce' => $this->nonceStore->create(),
            'ttl' => $this->nonceStore->getTtl(),
        ]);
    }
}
