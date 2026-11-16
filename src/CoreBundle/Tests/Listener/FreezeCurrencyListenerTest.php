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

namespace SolidInvoice\CoreBundle\Tests\Listener;

use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Currency\FrozenCurrencyThaw;
use SolidInvoice\CoreBundle\Listener\FreezeCurrencyListener;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(FreezeCurrencyListener::class)]
final class FreezeCurrencyListenerTest extends TestCase
{
    public function testInvoiceAcceptTransitionFreezesCurrencyFromClient(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
            'invoice',
        ));

        self::assertSame('EUR', $invoice->getCurrencyCode());
    }

    public function testInvoiceAlreadyFrozenCurrencyIsNotOverwritten(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCurrencyCode('USD');

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
            'invoice',
        ));

        self::assertSame('USD', $invoice->getCurrencyCode());
    }

    public function testInvoiceTransitionBackIntoDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('edit', InvoiceStatus::Pending->value, InvoiceStatus::Draft->value),
            'invoice',
        ));

        self::assertNull($invoice->getCurrencyCode());
    }

    /**
     * `cancel` leaves Draft but does not issue the invoice. A cancelled draft can be
     * reopened and re-pointed at another client, so it must stay mutable.
     */
    public function testInvoiceCancelFromDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('cancel', InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value),
            'invoice',
        ));

        self::assertNull($invoice->getCurrencyCode());
    }

    public function testInvoiceArchiveFromDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('archive', InvoiceStatus::Draft->value, InvoiceStatus::Archived->value),
            'invoice',
        ));

        self::assertNull($invoice->getCurrencyCode());
    }

    #[DoesNotPerformAssertions]
    public function testNonFrozenCurrencySubjectIsIgnoredOnInvoiceTransition(): void
    {
        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());

        $listener->onInvoiceTransition($this->makeEvent(
            new Client(),
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
            'invoice',
        ));
    }

    public function testQuoteSendTransitionFreezesCurrencyFromClient(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onQuoteTransition($this->makeEvent(
            $quote,
            new Transition('send', QuoteStatus::Draft->value, QuoteStatus::Pending->value),
            'quote',
        ));

        self::assertSame('EUR', $quote->getCurrencyCode());
    }

    public function testQuoteAlreadyFrozenCurrencyIsNotOverwritten(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);
        $quote->setCurrencyCode('USD');

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onQuoteTransition($this->makeEvent(
            $quote,
            new Transition('send', QuoteStatus::Draft->value, QuoteStatus::Pending->value),
            'quote',
        ));

        self::assertSame('USD', $quote->getCurrencyCode());
    }

    public function testQuoteTransitionBackIntoDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onQuoteTransition($this->makeEvent(
            $quote,
            new Transition('reopen', QuoteStatus::Declined->value, QuoteStatus::Draft->value),
            'quote',
        ));

        self::assertNull($quote->getCurrencyCode());
    }

    /**
     * `cancel` leaves Draft but does not issue the quote. A cancelled draft can be
     * reopened and re-pointed at another client, so it must stay mutable.
     */
    public function testQuoteCancelFromDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onQuoteTransition($this->makeEvent(
            $quote,
            new Transition('cancel', QuoteStatus::Draft->value, QuoteStatus::Cancelled->value),
            'quote',
        ));

        self::assertNull($quote->getCurrencyCode());
    }

    public function testQuoteArchiveFromDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onQuoteTransition($this->makeEvent(
            $quote,
            new Transition('archive', QuoteStatus::Draft->value, QuoteStatus::Archived->value),
            'quote',
        ));

        self::assertNull($quote->getCurrencyCode());
    }

    #[DoesNotPerformAssertions]
    public function testNonFrozenCurrencySubjectIsIgnoredOnQuoteTransition(): void
    {
        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());

        $listener->onQuoteTransition($this->makeEvent(
            new Client(),
            new Transition('send', QuoteStatus::Draft->value, QuoteStatus::Pending->value),
            'quote',
        ));
    }

    /**
     * `reopen` is `[Cancelled] → [Draft]` — its only `from` is already a non-issued
     * place, so this is the path a froms-based predicate would miss.
     */
    public function testFrozenInvoiceReopenedFromCancelledIsThawedAndSanctioned(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCurrencyCode('USD');

        $thaw = new FrozenCurrencyThaw();
        $listener = new FreezeCurrencyListener($thaw);
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('reopen', InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value),
            'invoice',
        ));

        self::assertNull($invoice->getCurrencyCode());
        self::assertTrue($thaw->consume($invoice));
    }

    /**
     * An invoice with a payment row retains its frozen currency forever, per
     * {@see Invoice::retainsFrozenCurrency()}. It must not be thawed, and must not be
     * sanctioned either.
     */
    public function testFrozenInvoiceWithPaymentsIsNotThawedOnReopen(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCurrencyCode('USD');
        $invoice->addPayment(new Payment());

        $thaw = new FrozenCurrencyThaw();
        $listener = new FreezeCurrencyListener($thaw);
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('reopen', InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value),
            'invoice',
        ));

        self::assertSame('USD', $invoice->getCurrencyCode());
        self::assertFalse($thaw->consume($invoice));
    }

    /**
     * `cancel` leaves an issued invoice in Cancelled, not Draft, so an already-frozen
     * currency must stay frozen.
     */
    public function testFrozenInvoiceIsNotThawedWhenDestinationIsNotADraftPlace(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCurrencyCode('USD');

        $listener = new FreezeCurrencyListener(new FrozenCurrencyThaw());
        $listener->onInvoiceTransition($this->makeEvent(
            $invoice,
            new Transition('cancel', InvoiceStatus::Pending->value, InvoiceStatus::Cancelled->value),
            'invoice',
        ));

        self::assertSame('USD', $invoice->getCurrencyCode());
    }

    /**
     * @return Event<object>
     */
    private function makeEvent(object $subject, Transition $transition, string $workflowName): Event
    {
        $workflow = $this->createStub(WorkflowInterface::class);
        $workflow->method('getName')->willReturn($workflowName);

        return new Event($subject, new Marking(), $transition, $workflow);
    }
}
