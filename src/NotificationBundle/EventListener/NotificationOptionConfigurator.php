<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\EventListener;

use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use SolidInvoice\NotificationBundle\Notification\Options\Reference\TemplateReference;
use SolidInvoice\NotificationBundle\Notification\Options\Reference\TranslationReference;
use SolidInvoice\NotificationBundle\Notification\Options\Reference\UrlRouteReference;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_key_exists;

class NotificationOptionConfigurator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
        private readonly TransportSettingRepository $transportSettingRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener(MessageEvent::class)]
    public function configureOptions(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (! $message instanceof ChatMessage) {
            return;
        }

        $message->subject($this->translator->trans($message->getSubject(), [], 'email'));

        $notification = $message->getNotification();

        if (! $notification instanceof NotificationMessage) {
            return;
        }

        $notification->subject($this->translator->trans($notification->getSubject(), [], 'email'));

        $notification->content($notification->getTextContent($this->twig));

        $this->setMessageOptions($message);
    }

    private function setMessageOptions(ChatMessage $message): void
    {
        $transportConfig = $this->transportSettingRepository->find($message->getTransport());

        if (! $transportConfig instanceof TransportSetting) {
            return;
        }

        $options = $message->getOptions()?->toArray() ?? [];

        if (array_key_exists($transportConfig->getTransport(), $options) && $options[$transportConfig->getTransport()] instanceof MessageOptionsInterface) {
            $resolveOptions = $this->resolveOptions($options[$transportConfig->getTransport()]->toArray());

            $message->options(new ($options[$transportConfig->getTransport()])($resolveOptions));
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    private function resolveOptions(array $options): array
    {
        $resolvedOptions = [];

        foreach ($options as $key => $option) {
            $resolvedOptions[$key] = match (true) {
                $option instanceof UrlRouteReference => $this->urlGenerator->generate($option->routeName, $option->routeParameters, UrlGeneratorInterface::ABSOLUTE_URL),
                $option instanceof TranslationReference => $this->translator->trans($option->translationId, $option->parameters),
                $option instanceof TemplateReference => $this->twig->render($option->template, $option->parameters),
                $option instanceof MessageOptionsInterface => $this->resolveOptions($option->toArray()),
                is_array($option) => $this->resolveOptions($option),
                default => $option,
            };
        }

        return $resolvedOptions;
    }
}
