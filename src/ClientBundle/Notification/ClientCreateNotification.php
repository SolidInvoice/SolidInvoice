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

namespace SolidInvoice\ClientBundle\Notification;

use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use SolidInvoice\NotificationBundle\Notification\Options\Reference\TemplateReference;
use SolidInvoice\NotificationBundle\Notification\Options\Reference\TranslationReference;
use SolidInvoice\NotificationBundle\Notification\Options\Reference\UrlRouteReference;
use SolidInvoice\NotificationBundle\Notification\Options\SimpleMessageOptions;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Twig\Environment;

#[AsNotification(name: ClientCreateNotification::EVENT)]
class ClientCreateNotification extends NotificationMessage
{
    public const EVENT = 'client_create';

    final public const HTML_TEMPLATE = '@SolidInvoiceClient/Email/client_create.html.twig';

    final public const TEXT_TEMPLATE = '@SolidInvoiceClient/Email/client_create.text.twig';

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    public function getSubject(): string
    {
        return 'A new client has been created';
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $message = parent::asEmailMessage($recipient, $transport);

        $email = $message->getMessage();

        if ($email instanceof NotificationEmail) {
            $email->textTemplate(self::TEXT_TEMPLATE);
            $email->htmlTemplate(self::HTML_TEMPLATE);
            $email->context($this->getParameters());
        }

        return $message;
    }

    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ChatMessage
    {
        return parent::asChatMessage($recipient, $transport)
            ->options(new SimpleMessageOptions([
                'Slack' => new SlackOptions($this->getSlackOptions()),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function getSlackOptions(): array
    {
        $slackOptions = SlackOptions::fromNotification($this)->toArray();

        $slackOptions['blocks'][] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => new TemplateReference(self::TEXT_TEMPLATE, $this->getParameters()),
            ],
        ];

        $slackOptions['blocks'][] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'style' => 'primary',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => new TranslationReference('View Client'),
                    ],
                    'url' => new UrlRouteReference('_clients_view', ['id' => $this->getParameters()['client']->getId()->toString()]),
                ],
            ],
        ];

        return $slackOptions;
    }
}
