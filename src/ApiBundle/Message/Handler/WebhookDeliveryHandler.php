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

namespace SolidInvoice\ApiBundle\Message\Handler;

use Psr\Log\LoggerInterface;
use SolidInvoice\ApiBundle\Message\WebhookDelivery;
use SolidInvoice\ApiBundle\Repository\WebhookRepository;
use SolidInvoice\ApiBundle\Webhook\WebhookUrlPolicy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final class WebhookDeliveryHandler
{
    public function __construct(
        private readonly WebhookRepository $webhookRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly WebhookUrlPolicy $webhookUrlPolicy,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'solidinvoice.webhook_timeout')]
        private readonly int $webhookTimeout,
    ) {
    }

    public function __invoke(WebhookDelivery $message): void
    {
        $webhook = $this->webhookRepository->find($message->webhookId);

        if ($webhook === null || ! $webhook->isActive()) {
            return;
        }

        if (! $this->webhookUrlPolicy->isAllowed($webhook->getUrl())) {
            return;
        }

        $signature = 'sha256=' . hash_hmac('sha256', $message->payload, $webhook->getSecret());

        $response = $this->httpClient->request('POST', $webhook->getUrl(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-SolidInvoice-Event' => $message->event,
                'X-SolidInvoice-Signature' => $signature,
            ],
            'body' => $message->payload,
            'timeout' => $this->webhookTimeout,
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger->warning('Webhook delivery to "{url}" returned HTTP {status}', [
                'url' => $webhook->getUrl(),
                'status' => $statusCode,
                'event' => $message->event,
                'webhook_id' => $webhook->getId(),
            ]);
        }
    }
}
