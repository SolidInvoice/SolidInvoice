<?php

declare(strict_types=1);

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
use CSBill\NotificationBundle\Notification\NotificationManager;
use CSBill\QuoteBundle\Entity\Item;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Event\QuoteEvent;
use CSBill\QuoteBundle\Event\QuoteEvents;
use CSBill\QuoteBundle\Exception\InvalidTransitionException;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Notification\QuoteStatusNotification;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Workflow\StateMachine;

class QuoteManager
{
    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var NotificationManager
     */
    private $notification;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @param ManagerRegistry          $doctrine
     * @param StateMachine             $stateMachine
     * @param Mailer                   $mailer
     * @param NotificationManager      $notification
     */
    public function __construct(
        ManagerRegistry $doctrine,
        StateMachine $stateMachine,
        Mailer $mailer,
        NotificationManager $notification
    ) {
        $this->stateMachine = $stateMachine;
        $this->notification = $notification;
        $this->mailer = $mailer;
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
        if ($this->stateMachine->can($quote, $transition)) {
            $oldStatus = $quote->getStatus();

            $this->stateMachine->apply($quote, $transition);

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
    public function send(Quote $quote): Quote
    {
        if (Graph::STATUS_DRAFT === $quote->getStatus()) {
            $this->applyTransition($quote, Graph::TRANSITION_SEND);
        }

        $this->mailer->sendQuote($quote);

        return $quote;
    }

    /**
     * @param Quote $quote
     *
     * @return Quote
     */
    public function duplicate(Quote $quote): Quote
    {
        // We don't use 'clone', since cloning a quote will clone all the item id's and nested values.
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

        if ($this->stateMachine->can($newQuote, Graph::TRANSITION_NEW)) {
            $this->stateMachine->apply($newQuote, Graph::TRANSITION_NEW);
        }

        return $newQuote;
    }
}
