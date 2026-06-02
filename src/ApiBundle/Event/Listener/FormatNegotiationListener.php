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

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * When a URL extension (e.g. .json, .jsonld, .xml) explicitly pins a format,
 * ensure the Accept header includes the corresponding MIME type so API Platform's
 * content negotiation does not reject the request with 406.
 *
 * Bots and crawlers often send generic HTML Accept headers while targeting
 * typed URLs like /api/docs.json; the URL extension should take precedence.
 * @see \SolidInvoice\ApiBundle\Tests\Event\Listener\FormatNegotiationListenerTest
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 4)]
final readonly class FormatNegotiationListener
{
    /** @var array<string, string> Maps Symfony request-format names to their primary MIME type. */
    private const array FORMAT_MIME_MAP = [
        'json' => 'application/json',
        'jsonld' => 'application/ld+json',
        'jsonopenapi' => 'application/vnd.openapi+json',
        'xml' => 'application/xml',
        'html' => 'text/html',
    ];

    public function __invoke(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (! str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // getRequestFormat(null) returns null when no _format was set by the router;
        // passing null avoids the 'html' fallback that would match any Accept header.
        $requestFormat = $request->getRequestFormat(null);

        if ($requestFormat === null || ! isset(self::FORMAT_MIME_MAP[$requestFormat])) {
            return;
        }

        $expectedMime = self::FORMAT_MIME_MAP[$requestFormat];
        $accept = $request->headers->get('Accept', '');

        if ($accept === '' || str_contains((string) $accept, $expectedMime) || str_contains((string) $accept, '*/*')) {
            return;
        }

        // The URL extension explicitly requests a specific format but the Accept header
        // (e.g. from a crawler) does not include the corresponding MIME type.
        // Override it so content negotiation can succeed.
        $request->headers->set('Accept', $expectedMime);
    }
}
