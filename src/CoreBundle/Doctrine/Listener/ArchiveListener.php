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

namespace SolidInvoice\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Mapping\MappedEventSubscriber;

/**
 * @extends MappedEventSubscriber<array, AdapterInterface>
 */
class ArchiveListener extends MappedEventSubscriber
{
    /**
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            'loadClassMetadata',
        ];
    }

    /**
     * Maps additional metadata.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    protected function getNamespace(): string
    {
        return __NAMESPACE__;
    }
}
