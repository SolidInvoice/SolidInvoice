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

namespace SolidInvoice\CoreBundle\Tests\Listener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use LogicException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Listener\Doctrine\FrozenCurrencyGuardListener;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;

final class FrozenCurrencyGuardListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAllowsTheFirstWriteFromNullOnAnInvoice(): void
    {
        $entity = new Invoice();
        $changeSet = ['currencyCode' => [null, 'USD']];

        $listener = new FrozenCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }

    public function testRejectsAChangeOnceFrozenOnAnInvoice(): void
    {
        $entity = new Invoice();
        $changeSet = ['currencyCode' => ['USD', 'EUR']];

        $listener = new FrozenCurrencyGuardListener();

        $this->expectException(LogicException::class);

        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));
    }

    public function testIgnoresUpdatesThatDoNotTouchCurrencyCodeOnAnInvoice(): void
    {
        $entity = new Invoice();
        $changeSet = ['invoiceId' => ['INV-1', 'INV-2']];

        $listener = new FrozenCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }

    public function testAllowsTheFirstWriteFromNullOnAQuote(): void
    {
        $entity = new Quote();
        $changeSet = ['currencyCode' => [null, 'USD']];

        $listener = new FrozenCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }

    public function testRejectsAChangeOnceFrozenOnAQuote(): void
    {
        $entity = new Quote();
        $changeSet = ['currencyCode' => ['USD', 'EUR']];

        $listener = new FrozenCurrencyGuardListener();

        $this->expectException(LogicException::class);

        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));
    }

    public function testIgnoresUpdatesThatDoNotTouchCurrencyCodeOnAQuote(): void
    {
        $entity = new Quote();
        $changeSet = ['quoteId' => ['QUO-1', 'QUO-2']];

        $listener = new FrozenCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }

    public function testIgnoresEntitiesThatDoNotFreezeTheirCurrency(): void
    {
        $entity = new Client();
        $changeSet = ['currencyCode' => ['USD', 'EUR']];

        $listener = new FrozenCurrencyGuardListener();
        $listener->preUpdate(new PreUpdateEventArgs($entity, M::mock(EntityManagerInterface::class), $changeSet));

        $this->addToAssertionCount(1);
    }
}
