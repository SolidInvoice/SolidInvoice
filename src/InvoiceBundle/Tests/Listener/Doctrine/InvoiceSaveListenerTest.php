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

namespace CSBill\InvoiceBundle\Tests\Listener\Doctrine;

use CSBill\CoreBundle\Billing\TotalCalculator;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Listener\Doctrine\InvoiceSaveListener;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\QuoteBundle\Entity\Quote;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\StateMachine;

class InvoiceSaveListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEvents()
    {
        $listener = new InvoiceSaveListener(M::mock(TotalCalculator::class));
        $this->assertSame([Events::prePersist, Events::preUpdate], $listener->getSubscribedEvents());
    }

    public function testPrePersist()
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPrePersistOnlyCallsStateMachineWithNoStatus()
    {
        $entity = new Invoice();
        $entity->setStatus(Graph::STATUS_DRAFT);
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPreUpdate()
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPrePersistOnlyWorksWithInvoice()
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('apply')
            ->never();

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }

    public function testPreUpdateOnlyWorksWithInvoice()
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new InvoiceSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(ObjectManager::class)));
    }
}
