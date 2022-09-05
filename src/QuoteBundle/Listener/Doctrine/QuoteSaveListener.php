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

namespace SolidInvoice\QuoteBundle\Listener\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Doctrine\QuoteSaveListenerTest
 */
class QuoteSaveListener implements EventSubscriber
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    public function __construct(ServiceLocator $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
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

        if ($entity instanceof Quote) {
            $this->serviceLocator->get(TotalCalculator::class)->calculateTotals($entity);
            $this->checkDiscount($entity);
        }
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Quote) {
            $this->serviceLocator->get(TotalCalculator::class)->calculateTotals($entity);
            $this->checkDiscount($entity);
        }
    }

    private function checkDiscount(Quote $entity): void
    {
        $discount = $entity->getDiscount();
        if (! $discount->getValue()) {
            $discount->setType(null);
        }
    }
}
