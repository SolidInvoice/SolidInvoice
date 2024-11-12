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

namespace SolidInvoice\InvoiceBundle\Listener\Doctrine;

use Brick\Math\Exception\MathException;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Doctrine\InvoiceSaveListenerTest
 */
#[AsDoctrineListener(Events::prePersist)]
#[AsDoctrineListener(Events::preUpdate)]
final class InvoiceSaveListener
{
    public function __construct(
        private readonly TotalCalculator $totalCalculator
    ) {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function preUpdate(LifecycleEventArgs $event): void
    {
        $this->calculateTotals($event);
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->calculateTotals($event);
    }

    private function checkDiscount(BaseInvoice $entity): void
    {
        $discount = $entity->getDiscount();
        if (! $discount->getValue()) {
            $discount->setType(null);
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    private function calculateTotals(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof BaseInvoice) {
            try {
                $this->totalCalculator->calculateTotals($entity);
            } catch (MathException) {
            }

            $this->checkDiscount($entity);
        }
    }
}
