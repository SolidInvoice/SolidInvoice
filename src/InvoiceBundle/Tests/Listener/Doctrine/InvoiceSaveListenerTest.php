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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Doctrine\InvoiceSaveListener;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Entity\Quote;

class InvoiceSaveListenerTest extends TestCase
{
    public function testPrePersist(): void
    {
        $entity = new Invoice();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->once())
            ->method('calculateTotals')
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyCallsStateMachineWithNoStatus(): void
    {
        $entity = new Invoice();
        $entity->setStatus(Graph::STATUS_DRAFT);
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->once())
            ->method('calculateTotals')
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPreUpdate(): void
    {
        $entity = new Invoice();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->once())
            ->method('calculateTotals')
            ->with($entity);

        $listener = new InvoiceSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyWorksWithInvoice(): void
    {
        $entity = new Quote();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->never())
            ->method('calculateTotals');

        $listener = new InvoiceSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPreUpdateOnlyWorksWithInvoice(): void
    {
        $entity = new Quote();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->never())
            ->method('calculateTotals');

        $listener = new InvoiceSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }
}
