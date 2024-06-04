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

namespace SolidInvoice\QuoteBundle\Mailer;

use JsonException;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Notification\QuoteStatusNotification;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\StateMachine;

final class QuoteMailer
{
    public function __construct(
        private readonly StateMachine $quoteStateMachine,
        private readonly MailerInterface $mailer,
        private readonly NotificationManager $notification
    ) {
    }

    /**
     * @throws InvalidTransitionException|JsonException
     */
    private function applyTransition(Quote $quote): void
    {
        if (! $this->quoteStateMachine->can($quote, Graph::TRANSITION_SEND)) {
            throw new InvalidTransitionException(Graph::TRANSITION_SEND);
        }

        $oldStatus = $quote->getStatus();

        $this->quoteStateMachine->apply($quote, Graph::TRANSITION_SEND);

        $newStatus = $quote->getStatus();

        $parameters = [
            'quote' => $quote,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'transition' => Graph::TRANSITION_SEND,
        ];

        $this->notification->sendNotification(new QuoteStatusNotification($parameters));
    }

    /**
     * @throws InvalidTransitionException|TransportExceptionInterface|JsonException
     */
    public function send(Quote $quote): Quote
    {
        if (Graph::STATUS_DRAFT === $quote->getStatus()) {
            $this->applyTransition($quote);
        } else {
            $this->mailer->send(new QuoteEmail($quote));
        }

        return $quote;
    }
}
