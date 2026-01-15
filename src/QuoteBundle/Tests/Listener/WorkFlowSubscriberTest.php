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

use PHPUnit\Framework\MockObject\MockObject;
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
    use Factories;

    public function testOnQuoteAccepted(): void
    {
        $quote = new Quote();
        $invoice = new Invoice();

        /** @var InvoiceManager&MockObject $invoiceManager */
        $invoiceManager = $this->createMock(InvoiceManager::class);

        $invoiceManager->expects($this->once())
            ->method('createFromQuote')
            ->with($quote)
            ->willReturn($invoice);

        /** @var StateMachine&MockObject $stateMachine */
        $stateMachine = $this->createMock(StateMachine::class);

        $stateMachine->expects($this->once())
            ->method('apply')
            ->with($invoice, 'new')
            ->willReturn(new Marking());

        /** @var NotificationManager&MockObject $notification */
        $notification = $this->createMock(NotificationManager::class);

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);

        $subscriber = new WorkFlowSubscriber(
            $this->registry,
            $invoiceManager,
            $stateMachine,
            $notification,
            new QuoteMailer($stateMachine, $mailer, $notification)
        );

        $subscriber->onQuoteAccepted(new Event($quote, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived'), $this->createMock(WorkflowInterface::class)));
    }

    public function testOnWorkflowTransitionApplied(): void
    {
        $quote = (new Quote())
            ->setClient(ClientFactory::createOne()->_real())
            ->setStatus('pending');

        /** @var InvoiceManager&MockObject $invoiceManager */
        $invoiceManager = $this->createMock(InvoiceManager::class);

        /** @var StateMachine&MockObject $stateMachine */
        $stateMachine = $this->createMock(StateMachine::class);

        /** @var NotificationManager&MockObject $notification */
        $notification = $this->createMock(NotificationManager::class);

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);

        $subscriber = new WorkFlowSubscriber(
            $this->registry,
            $invoiceManager,
            $stateMachine,
            $notification,
            new QuoteMailer($stateMachine, $mailer, $notification)
        );

        $subscriber->onWorkflowTransitionApplied(new Event($quote, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived'), $this->createMock(WorkflowInterface::class)));

        self::assertTrue($quote->isArchived());
        self::assertSame($quote, $this->em->getRepository(Quote::class)->find($quote->getId()));
    }
}
