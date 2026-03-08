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

namespace SolidInvoice\CoreBundle\Search;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Meilisearch\Bundle\EventListener\DoctrineEventSubscriber;
use SolidWorx\Toggler\ToggleInterface;

final class DoctrineEventSubscriberDecorator
{
    public function __construct(
        private readonly DoctrineEventSubscriber $inner,
        private readonly ToggleInterface $toggle,
    ) {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        if ($this->toggle->isActive('meilisearch_search')) {
            $this->inner->postUpdate($args);
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if ($this->toggle->isActive('meilisearch_search')) {
            $this->inner->postPersist($args);
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        if ($this->toggle->isActive('meilisearch_search')) {
            $this->inner->preRemove($args);
        }
    }
}
