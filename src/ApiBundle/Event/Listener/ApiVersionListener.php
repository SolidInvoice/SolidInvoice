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

use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rejects an unknown `Api-Version` before the operation runs, and marks every response served in
 * the original (unversioned) format as deprecated, with the date it will be removed.
 *
 * @see \SolidInvoice\ApiBundle\Tests\Functional\Api\MoneyFormatTest
 */
final class ApiVersionListener
{
    // After routing (32), which marks API Platform requests with `_api_resource_class`.
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 30)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($event->isMainRequest() && $request->attributes->has('_api_resource_class')) {
            ApiVersion::fromRequest($request);
        }
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (! $event->isMainRequest() || ! $request->attributes->has('_api_resource_class')) {
            return;
        }

        if ($request->headers->has(ApiVersion::HEADER)) {
            return;
        }

        $headers = $event->getResponse()->headers;

        // An operation may already have flagged something narrower as deprecated; keep its header.
        if (! $headers->has('Deprecation')) {
            $headers->set('Deprecation', ApiVersion::LEGACY_DEPRECATION);
        }

        $headers->set('Sunset', ApiVersion::LEGACY_SUNSET);
    }
}
