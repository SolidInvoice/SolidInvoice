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

namespace SolidInvoice\InvoiceBundle\Tests\Listener;

use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Listener\FreezeInvoiceCurrencyListener;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(FreezeInvoiceCurrencyListener::class)]
final class FreezeInvoiceCurrencyListenerTest extends TestCase
{
    public function testAcceptTransitionFreezesCurrencyFromClient(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);

        $listener = new FreezeInvoiceCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $invoice,
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
        ));

        self::assertSame('EUR', $invoice->getCurrencyCode());
    }

    public function testAlreadyFrozenCurrencyIsNotOverwritten(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCurrencyCode('USD');

        $listener = new FreezeInvoiceCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $invoice,
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
        ));

        self::assertSame('USD', $invoice->getCurrencyCode());
    }

    public function testTransitionBackIntoDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);

        $listener = new FreezeInvoiceCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $invoice,
            new Transition('edit', InvoiceStatus::Pending->value, InvoiceStatus::Draft->value),
        ));

        self::assertNull($invoice->getCurrencyCode());
    }

    #[DoesNotPerformAssertions]
    public function testNonInvoiceSubjectIsIgnored(): void
    {
        $listener = new FreezeInvoiceCurrencyListener();

        $listener->onTransition($this->makeEvent(
            new Client(),
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
        ));
    }

    /**
     * @return Event<object>
     */
    private function makeEvent(object $subject, Transition $transition): Event
    {
        $workflow = $this->createStub(WorkflowInterface::class);
        $workflow->method('getName')->willReturn('invoice');

        return new Event($subject, new Marking(), $transition, $workflow);
    }
}
