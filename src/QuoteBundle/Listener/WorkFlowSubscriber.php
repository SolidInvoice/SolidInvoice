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

namespace SolidInvoice\QuoteBundle\Listener;

use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Mailer\QuoteMailer;
use SolidInvoice\QuoteBundle\Model\Graph as QuoteGraph;
use SolidInvoice\QuoteBundle\Notification\QuoteStatusNotification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\StateMachine;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\WorkFlowSubscriberTest
 */
final class WorkFlowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly InvoiceManager $invoiceManager,
        private readonly StateMachine $invoiceStateMachine,
        private readonly NotificationManager $notification,
        private readonly QuoteMailer $quoteMailer
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.quote.entered.accepted' => 'onQuoteAccepted',
            'workflow.quote.entered' => 'onWorkflowTransitionApplied',
        ];
    }

    public function onQuoteAccepted(Event $event): void
    {
        $quote = $event->getSubject();
        assert($quote instanceof Quote);
        $invoice = $this->invoiceManager->createFromQuote($quote);

        $this->invoiceStateMachine->apply($invoice, InvoiceGraph::TRANSITION_NEW);
    }

    /**
     * @throws JsonException|InvalidTransitionException|TransportExceptionInterface
     */
    public function onWorkflowTransitionApplied(Event $event): void
    {
        /** @var Quote $quote */
        $quote = $event->getSubject();

        if (null !== ($transition = $event->getTransition()) && QuoteGraph::TRANSITION_ARCHIVE === $transition->getName()) {
            $quote->archive();
        }

        $em = $this->registry->getManager();

        $em->persist($quote);
        $em->flush();

        if (null !== ($transition = $event->getTransition()) && QuoteGraph::TRANSITION_SEND === $transition->getName()) {
            $this->quoteMailer->send($quote);
        }

        if (QuoteGraph::STATUS_NEW !== $quote->getStatus()) {
            $this->notification->sendNotification(new QuoteStatusNotification(['quote' => $quote]));
        }
    }
}
