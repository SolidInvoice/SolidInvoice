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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Doctrine\QuoteSaveListener;
use SolidInvoice\QuoteBundle\Model\Graph;

class QuoteSaveListenerTest extends TestCase
{
    public function testPrePersist(): void
    {
        $entity = new Quote();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->once())
            ->method('calculateTotals')
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);

        $listener->prePersist(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyCallsStateMachineWithNoStatus(): void
    {
        $entity = new Quote();
        $entity->setStatus(Graph::STATUS_DRAFT);
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->once())
            ->method('calculateTotals')
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPreUpdate(): void
    {
        $entity = new Quote();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->once())
            ->method('calculateTotals')
            ->with($entity);

        $listener = new QuoteSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPrePersistOnlyWorksWithQuote(): void
    {
        $entity = new Invoice();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->never())
            ->method('calculateTotals');

        $listener = new QuoteSaveListener($calculator);
        $listener->prePersist(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }

    public function testPreUpdateOnlyWorksWithQuote(): void
    {
        $entity = new Invoice();
        /** @var TotalCalculator&MockObject $calculator */
        $calculator = $this->createMock(TotalCalculator::class);
        $calculator->expects($this->never())
            ->method('calculateTotals');

        $listener = new QuoteSaveListener($calculator);
        $listener->preUpdate(new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class)));
    }
}
