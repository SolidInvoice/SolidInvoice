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

namespace SolidInvoice\InvoiceBundle\Tests\Listener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Doctrine\InvoiceSaveListener;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Entity\Quote;

class InvoiceSaveListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPrePersist(): void
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyCallsStateMachineWithNoStatus(): void
    {
        $entity = new Invoice();
        $entity->setStatus(Graph::STATUS_DRAFT);
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPreUpdate(): void
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyWorksWithInvoice(): void
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPreUpdateOnlyWorksWithInvoice(): void
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new InvoiceSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }
}
