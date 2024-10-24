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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\QuoteBundle\Mailer\QuoteMailer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\QuoteBundle\Listener\WorkFlowSubscriber
 */
final class WorkFlowSubscriberTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;
    use Factories;

    public function testOnQuoteAccepted(): void
    {
        $quote = new Quote();
        $invoice = new Invoice();

        $invoiceManager = M::mock(InvoiceManager::class);

        $invoiceManager->shouldReceive('createFromQuote')
            ->with($quote)
            ->andReturn($invoice);

        $stateMachine = M::mock(StateMachine::class);

        $stateMachine->shouldReceive('apply')
            ->with($invoice, 'new');

        $stateMachine->shouldReceive('apply')
            ->with($invoice, 'accept');

        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();

        $subscriber = new WorkFlowSubscriber(
            $this->registry,
            $invoiceManager,
            $stateMachine,
            $notification,
            new QuoteMailer($stateMachine, M::mock(MailerInterface::class), $notification)
        );

        $subscriber->onQuoteAccepted(new Event($quote, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived'), M::mock(WorkflowInterface::class)));
    }

    public function testOnWorkflowTransitionApplied(): void
    {
        $quote = (new Quote())
            ->setClient(ClientFactory::createOne()->_real())
            ->setStatus('pending');

        $invoiceManager = M::mock(InvoiceManager::class);
        $stateMachine = M::mock(StateMachine::class);

        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();

        $subscriber = new WorkFlowSubscriber(
            $this->registry,
            $invoiceManager,
            $stateMachine,
            $notification,
            new QuoteMailer($stateMachine, M::mock(MailerInterface::class), $notification)
        );

        $subscriber->onWorkflowTransitionApplied(new Event($quote, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived'), M::mock(WorkflowInterface::class)));

        self::assertTrue($quote->isArchived());
        self::assertSame($quote, $this->em->getRepository(Quote::class)->find($quote->getId()));
    }
}
