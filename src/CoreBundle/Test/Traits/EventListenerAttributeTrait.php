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

namespace SolidInvoice\CoreBundle\Test\Traits;

use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

trait EventListenerAttributeTrait
{
    /**
     * Reads the #[AsEventListener] attributes on a listener's methods.
     *
     * @param class-string $class
     *
     * @return array<string, array{string, int}> event => [method, priority]
     */
    private static function listenedEvents(string $class): array
    {
        $events = [];

        foreach (new ReflectionClass($class)->getMethods() as $method) {
            foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
                $listener = $attribute->newInstance();
                $events[(string) $listener->event] = [$method->getName(), $listener->priority];
            }
        }

        return $events;
    }
}
