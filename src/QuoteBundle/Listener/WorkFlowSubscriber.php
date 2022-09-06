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
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph as QuoteGraph;
use SolidInvoice\QuoteBundle\Notification\QuoteStatusNotification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\StateMachine;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\WorkFlowSubscriberTest
 */
class WorkFlowSubscriber implements EventSubscriberInterface
{
    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    /**
     * @var StateMachine
     */
    private $invoiceStateMachine;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var NotificationManager
     */
    private $notification;

    public function __construct(ManagerRegistry $registry, InvoiceManager $invoiceManager, StateMachine $invoiceStateMachine, NotificationManager $notification)
    {
        $this->invoiceManager = $invoiceManager;
        $this->invoiceStateMachine = $invoiceStateMachine;
        $this->registry = $registry;
        $this->notification = $notification;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.quote.entered.accepted' => 'onQuoteAccepted',
            'workflow.quote.entered' => 'onWorkflowTransitionApplied',
        ];
    }

    public function onQuoteAccepted(Event $event)
    {
        $quote = $event->getSubject();
        assert($quote instanceof Quote);
        $invoice = $this->invoiceManager->createFromQuote($quote);

        $this->invoiceStateMachine->apply($invoice, InvoiceGraph::TRANSITION_NEW);
    }

    public function onWorkflowTransitionApplied(Event $event)
    {
        /** @var Quote $quote */
        $quote = $event->getSubject();

        if (null !== ($transition = $event->getTransition()) && QuoteGraph::TRANSITION_ARCHIVE === $transition->getName()) {
            $quote->archive();
        }

        $em = $this->registry->getManager();

        $em->persist($quote);
        $em->flush();

        if (QuoteGraph::STATUS_NEW !== $quote->getStatus()) {
            $this->notification->sendNotification('quote_status_update', new QuoteStatusNotification(['quote' => $quote]));
        }
    }
}
