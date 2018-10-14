<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Listener\Doctrine;

use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class InvoiceSaveListener implements EventSubscriber
{
    private $totalCalculator;

    public function __construct(TotalCalculator $totalCalculator)
    {
        $this->totalCalculator = $totalCalculator;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Invoice) {
            $this->totalCalculator->calculateTotals($entity);
            $this->checkDiscount($entity);
        }
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Invoice) {
            $this->totalCalculator->calculateTotals($entity);
            $this->checkDiscount($entity);
        }
    }

    private function checkDiscount(Invoice $entity): void
    {
        $discount = $entity->getDiscount();
        if (!$discount->getValue()) {
            $discount->setType(null);
        }
    }
}
