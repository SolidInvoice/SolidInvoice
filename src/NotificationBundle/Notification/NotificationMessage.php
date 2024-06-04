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

namespace SolidInvoice\NotificationBundle\Notification;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Twig\Environment;

abstract class NotificationMessage extends Notification implements EmailNotificationInterface, ChatNotificationInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $parameters;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [], string $subject = '', array $channels = [])
    {
        $this->parameters = $parameters;

        parent::__construct($subject, $channels);
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    abstract public function getTextContent(Environment $twig): string;

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient);

        $email = $message->getMessage();

        if ($email instanceof NotificationEmail) {
            $email->markAsPublic();
        }

        return $message;
    }

    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ChatMessage
    {
        return ChatMessage::fromNotification($this);
    }
}
