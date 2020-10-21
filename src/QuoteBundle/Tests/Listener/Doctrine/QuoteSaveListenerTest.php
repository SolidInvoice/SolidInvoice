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

namespace SolidInvoice\QuoteBundle\Tests\Listener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Doctrine\QuoteSaveListener;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Component\DependencyInjection\ServiceLocator;

class QuoteSaveListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEvents()
    {
        $listener = new QuoteSaveListener(new ServiceLocator([]));
        static::assertSame([Events::prePersist, Events::preUpdate], $listener->getSubscribedEvents());
    }

    public function testPrePersist()
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener(new ServiceLocator([TotalCalculator::class => function () use ($calculator) { return $calculator; }]));
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPrePersistOnlyCallsStateMachineWithNoStatus()
    {
        $entity = new Quote();
        $entity->setStatus(Graph::STATUS_DRAFT);
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener(new ServiceLocator([TotalCalculator::class => function () use ($calculator) { return $calculator; }]));
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPreUpdate()
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener(new ServiceLocator([TotalCalculator::class => function () use ($calculator) { return $calculator; }]));
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPrePersistOnlyWorksWithQuote()
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new QuoteSaveListener(new ServiceLocator([TotalCalculator::class => function () use ($calculator) { return $calculator; }]));
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPreUpdateOnlyWorksWithQuote()
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new QuoteSaveListener(new ServiceLocator([TotalCalculator::class => function () use ($calculator) { return $calculator; }]));
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }
}
