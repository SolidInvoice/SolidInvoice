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
use SolidInvoice\CoreBundle\Mailer\DocumentSendOptions;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Notification\QuoteStatusNotification;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class QuoteMailer
{
    /**
     * Workflow context key carrying the {@see DocumentSendOptions} of a send from draft.
     */
    public const string OPTIONS = 'send_options';

    public function __construct(
        private WorkflowInterface $quoteStateMachine,
        private MailerInterface $mailer,
        private NotificationManager $notification
    ) {
    }

    /**
     * @throws InvalidTransitionException|JsonException
     */
    private function applyTransition(Quote $quote, ?DocumentSendOptions $options): void
    {
        if (! $this->quoteStateMachine->can($quote, Graph::TRANSITION_SEND)) {
            throw new InvalidTransitionException(Graph::TRANSITION_SEND);
        }

        $oldStatus = $quote->getStatus();

        // The workflow subscriber sends the email once the quote is pending; the options ride along.
        $this->quoteStateMachine->apply($quote, Graph::TRANSITION_SEND, [self::OPTIONS => $options]);

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
    public function send(Quote $quote, ?DocumentSendOptions $options = null): Quote
    {
        if (QuoteStatus::Draft === $quote->getStatus()) {
            $this->applyTransition($quote, $options);
        } else {
            $this->mailer->send(new QuoteEmail($quote, $options));
        }

        return $quote;
    }
}
