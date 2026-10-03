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
use Doctrine\ORM\Event\PreUpdateEventArgs;
use LogicException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Doctrine\InvoiceCurrencyGuardListener;
use SolidInvoice\QuoteBundle\Entity\Quote;

final class InvoiceCurrencyGuardListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAllowsTheFirstWriteFromNull(): void
    {
        $entity = new Invoice();
        $changeSet = ['currencyCode' => [null, 'USD']];

        $listener = new InvoiceCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }

    public function testRejectsAChangeOnceFrozen(): void
    {
        $entity = new Invoice();
        $changeSet = ['currencyCode' => ['USD', 'EUR']];

        $listener = new InvoiceCurrencyGuardListener();

        $this->expectException(LogicException::class);

        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));
    }

    public function testIgnoresUpdatesThatDoNotTouchCurrencyCode(): void
    {
        $entity = new Invoice();
        $changeSet = ['invoiceId' => ['INV-1', 'INV-2']];

        $listener = new InvoiceCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }

    public function testIgnoresNonInvoiceEntities(): void
    {
        $entity = new Quote();
        $changeSet = ['currencyCode' => ['USD', 'EUR']];

        $listener = new InvoiceCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }
}
