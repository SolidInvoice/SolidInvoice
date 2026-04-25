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

namespace SolidInvoice\CoreBundle\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;

#[AsDoctrineListener(event: \Doctrine\ORM\Events::preRemove)]
final class CustomFieldValueCleanupListener
{
    public function __construct(
        private readonly CustomFieldValueRepository $values,
    ) {
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $entity = $event->getObject();

        $target = match (true) {
            $entity instanceof Client => CustomFieldTarget::CLIENT,
            $entity instanceof Contact => CustomFieldTarget::CONTACT,
            default => null,
        };

        if ($target === null || $entity->getId() === null) {
            return;
        }

        $this->values->deleteForRecord($target, $entity->getId());
    }
}
