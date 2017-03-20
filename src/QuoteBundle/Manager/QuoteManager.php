<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Manager;

use Carbon\Carbon;
use CSBill\CoreBundle\Mailer\Mailer;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\NotificationBundle\Notification\NotificationManager;
use CSBill\QuoteBundle\Entity\Item;
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
     *
     * @throws InvalidTransitionException
     */
    public function accept(Quote $quote): Invoice
    {
        $this->dispatcher->dispatch(QuoteEvents::QUOTE_PRE_ACCEPT, new QuoteEvent($quote));

        $invoice = $this->invoiceManager->createFromQuote($quote);

        $this->applyTransition($quote, Graph::TRANSITION_ACCEPT);

        $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_ACCEPT, new QuoteEvent($quote));

        return $invoice;
    }

    /**
     * @param Quote  $quote
     * @param string $transition
     *
     * @return bool
     *
     * @throws InvalidTransitionException
     */
    private function applyTransition(Quote $quote, string $transition): bool
    {
        $stateMachine = $this->stateMachine->get($quote, Graph::GRAPH);

        if ($stateMachine->can($transition)) {
            $oldStatus = $quote->getStatus();

            $stateMachine->apply($transition);

            $this->entityManager->persist($quote);
            $this->entityManager->flush();

            $newStatus = $quote->getStatus();

            $parameters = [
        'quote' => $quote,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'transition' => $transition,
        ];

            $notification = new QuoteStatusNotification($parameters);

            $this->notification->sendNotification('quote_status_update', $notification);

            return true;
        }

        throw new InvalidTransitionException($transition);
    }

    /**
     * @param Quote $quote
     *
     * @return Quote
     *
     * @throws InvalidTransitionException
     */
    public function archive(Quote $quote): Quote
    {
        $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_ARCHIVE, new QuoteEvent($quote));

        $this->applyTransition($quote, Graph::TRANSITION_ARCHIVE);
        $quote->archive();

        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_ARCHIVE, new QuoteEvent($quote));

        return $quote;
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws InvalidTransitionException
     */
    public function decline(Quote $quote): RedirectResponse
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
     *
     * @throws InvalidTransitionException
     */
    public function cancel(Quote $quote): RedirectResponse
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
     *
     * @throws InvalidTransitionException
     */
    public function reopen(Quote $quote): RedirectResponse
    {
        $this->applyTransition($quote, Graph::TRANSITION_REOPEN);

        return $quote;
    }

    /**
     * @param Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws InvalidTransitionException
     */
    public function send(Quote $quote): RedirectResponse
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
     * @param Quote $quote
     *
     * @return Quote
     */
    public function duplicate(Quote $quote): Quote
    {
        // We don't use 'clone', since cloning aq quote will clone all the item id's and nested values.
        // We rather set it manually
        $newQuote = new Quote();

        $now = Carbon::now();

        $newQuote->setCreated($now);
        $newQuote->setClient($quote->getClient());
        $newQuote->setBaseTotal($quote->getBaseTotal());
        $newQuote->setDiscount($quote->getDiscount());
        $newQuote->setNotes($quote->getNotes());
        $newQuote->setTotal($quote->getTotal());
        $newQuote->setTerms($quote->getTerms());
        $newQuote->setUsers($quote->getUsers()->toArray());

        if (null !== $quote->getTax()) {
            $newQuote->setTax($quote->getTax());
        }

        foreach ($quote->getItems() as $item) {
            $invoiceItem = new Item();
            $invoiceItem->setCreated($now);
            $invoiceItem->setTotal($item->getTotal());
            $invoiceItem->setDescription($item->getDescription());
            $invoiceItem->setPrice($item->getPrice());
            $invoiceItem->setQty($item->getQty());

            if (null !== $item->getTax()) {
                $invoiceItem->setTax($item->getTax());
            }

            $newQuote->addItem($invoiceItem);
        }

        $this->dispatcher->dispatch(QuoteEvents::QUOTE_PRE_CREATE, new QuoteEvent($newQuote));

        $stateMachine = $this->stateMachine->get($newQuote, Graph::GRAPH);

        if ($stateMachine->can(Graph::TRANSITION_NEW)) {
            $stateMachine->apply(Graph::TRANSITION_NEW);
        }

        $this->entityManager->persist($newQuote);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(QuoteEvents::QUOTE_POST_CREATE, new QuoteEvent($newQuote));

        return $newQuote;
    }
}
