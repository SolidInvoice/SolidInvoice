<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Listener;

use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\InvoiceBundle\Model\Graph as InvoiceGraph;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Model\Graph as QuoteGraph;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\StateMachine;

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

    public function __construct(ManagerRegistry $registry, InvoiceManager $invoiceManager, StateMachine $invoiceStateMachine)
    {
        $this->invoiceManager = $invoiceManager;
        $this->invoiceStateMachine = $invoiceStateMachine;
        $this->registry = $registry;
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
        $invoice = $this->invoiceManager->createFromQuote($event->getSubject());

        $this->invoiceStateMachine->apply($invoice, InvoiceGraph::TRANSITION_NEW);
        $this->invoiceStateMachine->apply($invoice, InvoiceGraph::TRANSITION_ACCEPT);
    }

    public function onWorkflowTransitionApplied(Event $event)
    {
        /** @var Quote $quote */
        $quote = $event->getSubject();

        if (QuoteGraph::TRANSITION_ARCHIVE === $event->getTransition()) {
            $quote->archive();
        }

        $em = $this->registry->getManager();

        $em->persist($quote);
        $em->flush();
    }
}