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
 * Legacy Gedmo extension scaffolding. The class still resolves against the
 * installed Gedmo {@see MappedEventSubscriber} base under ORM 3, but it is
 * intentionally NOT registered as a Doctrine listener: it has no Gedmo mapping
 * driver under {@see __NAMESPACE__}\Mapping\Driver, so firing loadClassMetadata
 * would throw a RuntimeException from Gedmo's ExtensionMetadataFactory. The
 * archivable behaviour is provided entirely by the Archivable trait column
 * mapping and the ArchivableFilter SQL filter, so this listener adds no
 * behaviour and must remain unregistered.
 *
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
