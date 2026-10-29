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

namespace SolidInvoice\TaxBundle\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * A company has at most one default tax: making one the default takes it away from the others.
 */
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Tax::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Tax::class)]
final class DefaultTaxListener
{
    public function postPersist(Tax $tax, PostPersistEventArgs $event): void
    {
        $this->clearOtherDefaults($tax, $event);
    }

    public function postUpdate(Tax $tax, PostUpdateEventArgs $event): void
    {
        $this->clearOtherDefaults($tax, $event);
    }

    private function clearOtherDefaults(Tax $tax, PostPersistEventArgs | PostUpdateEventArgs $event): void
    {
        if (! $tax->isDefault()) {
            return;
        }

        $event->getObjectManager()->createQueryBuilder()
            ->update(Tax::class, 't')
            ->set('t.default', ':false')
            ->where('t.company = :company')
            ->andWhere('t.id != :id')
            ->andWhere('t.default = :true')
            ->setParameter('false', false)
            ->setParameter('true', true)
            ->setParameter('company', $tax->getCompany()->getId(), UlidType::NAME)
            ->setParameter('id', $tax->getId(), UlidType::NAME)
            ->getQuery()
            ->execute();
    }
}
