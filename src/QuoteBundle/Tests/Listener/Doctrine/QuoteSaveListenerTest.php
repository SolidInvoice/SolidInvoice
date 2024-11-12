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

namespace SolidInvoice\QuoteBundle\Tests\Listener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Doctrine\QuoteSaveListener;
use SolidInvoice\QuoteBundle\Model\Graph;

class QuoteSaveListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPrePersist(): void
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);

        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyCallsStateMachineWithNoStatus(): void
    {
        $entity = new Quote();
        $entity->setStatus(Graph::STATUS_DRAFT);
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPreUpdate(): void
    {
        $entity = new Quote();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->once()
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyWorksWithQuote(): void
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new QuoteSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }

    public function testPreUpdateOnlyWorksWithQuote(): void
    {
        $entity = new Invoice();
        $calculator = M::mock(TotalCalculator::class);
        $calculator->shouldReceive('calculateTotals')
            ->never();

        $listener = new QuoteSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, M::mock(EntityManagerInterface::class)));
    }
}
