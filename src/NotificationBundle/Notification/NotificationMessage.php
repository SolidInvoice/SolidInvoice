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
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private array $parameters = [],
        string $subject = '',
        array $channels = []
    ) {
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

    /**
     * Translation key (in the "email" domain) for the subject. NotificationManager
     * translates it with getSubjectParameters() and stores the result as the subject.
     */
    public function getSubjectKey(): string
    {
        return parent::getSubject();
    }

    /**
     * @return array<string, string>
     */
    public function getSubjectParameters(): array
    {
        return [];
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient);

        $email = $message->getMessage();

        if ($email instanceof NotificationEmail) {
            $email->markAsPublic();
        }

        return $message;
    }

    /**
     * Email template context. The notification_email flag turns on the footer (company and a link to
     * the user's notification preferences); some templates are shared with client-facing emails,
     * which must not show it.
     *
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    protected function notificationEmailContext(array $parameters): array
    {
        return $parameters + ['notification_email' => true];
    }

    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ChatMessage
    {
        return ChatMessage::fromNotification($this);
    }
}
