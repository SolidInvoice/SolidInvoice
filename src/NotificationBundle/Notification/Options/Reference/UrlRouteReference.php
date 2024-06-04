<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Notification\Options\Reference;

final class UrlRouteReference
{
    /**
     * @param array<string, mixed> $routeParameters
     */
    public function __construct(
        public readonly string $routeName,
        public readonly array $routeParameters = [],
    ) {
    }
}
