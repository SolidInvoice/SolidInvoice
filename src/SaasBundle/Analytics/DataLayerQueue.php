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

namespace SolidInvoice\SaasBundle\Analytics;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Session-backed queue of Google Tag Manager dataLayer events. Events queued
 * during a request (often one that redirects) are pushed to the dataLayer on
 * the next HTML page by {@see \SolidInvoice\SaasBundle\EventSubscriber\DataLayerListener}.
 */
final readonly class DataLayerQueue
{
    private const string SESSION_KEY = 'saas.data_layer_events';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<string, scalar> $event
     */
    public function push(array $event): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, [...$session->get(self::SESSION_KEY, []), $event]);
    }

    /**
     * @return list<array<string, scalar>>
     */
    public function pull(): array
    {
        return $this->requestStack->getSession()->remove(self::SESSION_KEY) ?? [];
    }
}
