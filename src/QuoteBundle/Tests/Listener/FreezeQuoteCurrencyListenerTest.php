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

namespace SolidInvoice\QuoteBundle\Tests\Listener;

use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Listener\FreezeQuoteCurrencyListener;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(FreezeQuoteCurrencyListener::class)]
final class FreezeQuoteCurrencyListenerTest extends TestCase
{
    public function testSendTransitionFreezesCurrencyFromClient(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeQuoteCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $quote,
            new Transition('send', QuoteStatus::Draft->value, QuoteStatus::Pending->value),
        ));

        self::assertSame('EUR', $quote->getCurrencyCode());
    }

    public function testAlreadyFrozenCurrencyIsNotOverwritten(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);
        $quote->setCurrencyCode('USD');

        $listener = new FreezeQuoteCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $quote,
            new Transition('send', QuoteStatus::Draft->value, QuoteStatus::Pending->value),
        ));

        self::assertSame('USD', $quote->getCurrencyCode());
    }

    public function testTransitionBackIntoDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeQuoteCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $quote,
            new Transition('reopen', QuoteStatus::Declined->value, QuoteStatus::Draft->value),
        ));

        self::assertNull($quote->getCurrencyCode());
    }

    /**
     * `cancel` leaves Draft but does not issue the quote. A cancelled draft can be
     * reopened and re-pointed at another client, so it must stay mutable.
     */
    public function testCancelFromDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeQuoteCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $quote,
            new Transition('cancel', QuoteStatus::Draft->value, QuoteStatus::Cancelled->value),
        ));

        self::assertNull($quote->getCurrencyCode());
    }

    public function testArchiveFromDraftDoesNotFreeze(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);

        $listener = new FreezeQuoteCurrencyListener();
        $listener->onTransition($this->makeEvent(
            $quote,
            new Transition('archive', QuoteStatus::Draft->value, QuoteStatus::Archived->value),
        ));

        self::assertNull($quote->getCurrencyCode());
    }

    #[DoesNotPerformAssertions]
    public function testNonQuoteSubjectIsIgnored(): void
    {
        $listener = new FreezeQuoteCurrencyListener();

        $listener->onTransition($this->makeEvent(
            new Client(),
            new Transition('send', QuoteStatus::Draft->value, QuoteStatus::Pending->value),
        ));
    }

    /**
     * @return Event<object>
     */
    private function makeEvent(object $subject, Transition $transition): Event
    {
        $workflow = $this->createStub(WorkflowInterface::class);
        $workflow->method('getName')->willReturn('quote');

        return new Event($subject, new Marking(), $transition, $workflow);
    }
}
