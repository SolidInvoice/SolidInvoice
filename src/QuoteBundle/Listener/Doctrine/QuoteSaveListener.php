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
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Doctrine\QuoteSaveListenerTest
 */
class QuoteSaveListener implements EventSubscriber
{
    public function __construct(private readonly ServiceLocator $serviceLocator)
    {
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function preUpdate(LifecycleEventArgs $event): void
    {
        $this->calculateQuoteTotals($event);
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->calculateQuoteTotals($event);
    }

    private function checkDiscount(Quote $entity): void
    {
        $discount = $entity->getDiscount();

        if (! $discount->getValue()) {
            $discount->setType(null);
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    private function calculateQuoteTotals(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof Quote) {
            try {
                $this->serviceLocator->get(TotalCalculator::class)->calculateTotals($entity);
            } catch (NotFoundExceptionInterface | ContainerExceptionInterface) {
            }

            $this->checkDiscount($entity);
        }
    }
}
