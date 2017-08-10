<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Tests\Listener\Doctrine;

use CSBill\CoreBundle\Billing\TotalCalculator;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Listener\Doctrine\QuoteSaveListener;
use CSBill\QuoteBundle\Model\Graph;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class QuoteSaveListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEvents()
    {
        $listener = new QuoteSaveListener(M::mock(TotalCalculator::class));
        $this->assertSame([Events::prePersist, Events::preUpdate], $listener->getSubscribedEvents());
    }

    public function testPrePersist()
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);
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

        $listener = new QuoteSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPreUpdate()
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPrePersistOnlyWorksWithQuote()
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new QuoteSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPreUpdateOnlyWorksWithQuote()
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new QuoteSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }
}
