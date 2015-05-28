<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Manager;

use CSBill\CoreBundle\Mailer\Mailer;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\NotificationBundle\Notification\NotificationManager;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Event\QuoteEvent;
use CSBill\QuoteBundle\Event\QuoteEvents;
use CSBill\QuoteBundle\Exception\InvalidTransitionException;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Notification\QuoteStatusNotification;
use Doctrine\Common\Persistence\ManagerRegistry;
use Finite\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QuoteManager
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $entityManager;

    /**
     * @var FactoryInterface
     */
    private $stateMachine;

    /**
     * @var NotificationManager
     */
    private $notification;

    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @param ManagerRegistry          $doctrine
     * @param EventDispatcherInterface $dispatcher
     * @param FactoryInterface         $stateMachine
     * @param InvoiceManager           $invoiceManager
     * @param Mailer                   $mailer
     * @param NotificationManager      $notification
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EventDispatcherInterface $dispatcher,
        FactoryInterface $stateMachine,
        InvoiceManager $invoiceManager,
        Mailer $mailer,
        NotificationManager $notification
    ) {
        $this->entityManager = $doctrine->getManager();
        $this->dispatcher = $dispatcher;
        $this->stateMachine = $stateMachine;
        $this->notification = $notification;
        $this->invoiceManager = $invoiceManager;
        $this->mailer = $mailer;
    }

    /**
     * @param Quote $quote
     *
     * @return Invoice
     * @throws InvalidTransitionException
     */
    public function accept(Quote $quote)
    {
        $this->dispatcher->dispatch(QuoteEvents::QUOTE_PRE_ACCEPT, new QuoteEvent($quote));

        $invoice = $this->invoiceManager->createFromQuote($quote);

        $this->applyTransition($quote, Graph::TRANSITION_ACCEPT);

        $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_ACCEPT, new QuoteEvent($quote));

        return $invoice;
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function decline(Quote $quote)
    {
        $this->dispatcher->dispatch(QuoteEvents::QUOTE_PRE_DECLINE, new QuoteEvent($quote));

        $this->applyTransition($quote, Graph::TRANSITION_DECLINE);

        $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_DECLINE, new QuoteEvent($quote));

        return $quote;
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function cancel(Quote $quote)
    {
        $this->dispatcher->dispatch(QuoteEvents::QUOTE_PRE_CANCEL, new QuoteEvent($quote));

        $this->applyTransition($quote, Graph::TRANSITION_CANCEL);

        $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_CANCEL, new QuoteEvent($quote));

        return $quote;
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function reopen(Quote $quote)
    {
        $this->applyTransition($quote, Graph::TRANSITION_REOPEN);

        return $quote;
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws InvalidTransitionException
     */
    public function send(Quote $quote)
    {
        $finite = $this->stateMachine->get($quote, Graph::GRAPH);

        if ($quote->getStatus() !== Graph::STATUS_PENDING) {
            if (!$finite->can(Graph::TRANSITION_SEND)) {
                throw new InvalidTransitionException(Graph::TRANSITION_SEND);
            }

            $this->dispatcher->dispatch(QuoteEvents::QUOTE_PRE_SEND, new QuoteEvent($quote));
            $this->applyTransition($quote, Graph::TRANSITION_SEND);
            $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_SEND, new QuoteEvent($quote));
        } else {
            $this->mailer->sendQuote($quote);
        }

        return $quote;
    }

    /**
     * @param Quote  $quote
     * @param string $transition
     *
     * @return bool
     * @throws InvalidTransitionException
     */
    private function applyTransition(Quote $quote, $transition)
    {
        $stateMachine = $this->stateMachine->get($quote, Graph::GRAPH);

        if ($stateMachine->can($transition)) {
            $oldStatus = $quote->getStatus();

            $stateMachine->apply($transition);

            $this->entityManager->persist($quote);
            $this->entityManager->flush();

            $newStatus = $quote->getStatus();

            $parameters = array(
                'quote' => $quote,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'transition' => $transition
            );

            $notification = new QuoteStatusNotification($parameters);

            $this->notification->sendNotification('quote_status_update', $notification);

            return true;
        }

        throw new InvalidTransitionException($transition);
    }
}