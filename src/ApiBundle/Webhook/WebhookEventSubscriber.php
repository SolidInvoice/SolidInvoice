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

namespace SolidInvoice\ApiBundle\Webhook;

use Psr\Log\LoggerInterface;
use SolidInvoice\ApiBundle\Message\WebhookDelivery;
use SolidInvoice\ApiBundle\Repository\WebhookRepository;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use SolidInvoice\QuoteBundle\Event\QuoteEvent;
use SolidInvoice\QuoteBundle\Event\QuoteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;
use Throwable;

final class WebhookEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WebhookRepository $webhookRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly WebhookPayloadBuilder $payloadBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.invoice.entered.paid' => 'onInvoicePaid',
            'workflow.invoice.entered.pending' => 'onInvoiceSent',
            'workflow.quote.entered.accepted' => 'onQuoteAccepted',
            'workflow.quote.entered.pending' => 'onQuoteSent',
            InvoiceEvents::INVOICE_POST_CREATE => 'onInvoiceCreated',
            QuoteEvents::QUOTE_POST_CREATE => 'onQuoteCreated',
        ];
    }

    public function onInvoicePaid(WorkflowEvent $event): void
    {
        $this->dispatchWebhooks('invoice.paid', $event->getSubject());
    }

    public function onInvoiceSent(WorkflowEvent $event): void
    {
        $this->dispatchWebhooks('invoice.sent', $event->getSubject());
    }

    public function onQuoteAccepted(WorkflowEvent $event): void
    {
        $this->dispatchWebhooks('quote.accepted', $event->getSubject());
    }

    public function onQuoteSent(WorkflowEvent $event): void
    {
        $this->dispatchWebhooks('quote.sent', $event->getSubject());
    }

    public function onInvoiceCreated(InvoiceEvent $event): void
    {
        $this->dispatchWebhooks('invoice.created', $event->getInvoice());
    }

    public function onQuoteCreated(QuoteEvent $event): void
    {
        $this->dispatchWebhooks('quote.created', $event->getQuote());
    }

    private function dispatchWebhooks(string $event, object $entity): void
    {
        try {
            $webhooks = $this->webhookRepository->findActiveByEvent($event);

            foreach ($webhooks as $webhook) {
                $payload = $this->payloadBuilder->build($entity, $event);

                $this->messageBus->dispatch(new WebhookDelivery(
                    $webhook->getId(),
                    $event,
                    $payload,
                ));
            }
        } catch (Throwable $e) {
            $this->logger->error('Failed to dispatch webhook for event "{event}": {message}', [
                'event' => $event,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
