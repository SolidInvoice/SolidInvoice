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

namespace CSBill\QuoteBundle\Manager;

use CSBill\CoreBundle\Mailer\Mailer;
use CSBill\NotificationBundle\Notification\NotificationManager;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Exception\InvalidTransitionException;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Notification\QuoteStatusNotification;
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
     * @param StateMachine        $stateMachine
     * @param Mailer              $mailer
     * @param NotificationManager $notification
     */
    public function __construct(
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
}
