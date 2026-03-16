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

use SolidInvoice\ApiBundle\Message\WebhookDelivery;
use SolidInvoice\ApiBundle\Repository\WebhookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final class WebhookDeliveryHandler
{
    public function __construct(
        private readonly WebhookRepository $webhookRepository,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function __invoke(WebhookDelivery $message): void
    {
        $webhook = $this->webhookRepository->find($message->webhookId);

        if ($webhook === null) {
            return;
        }

        $signature = 'sha256=' . hash_hmac('sha256', $message->payload, $webhook->getSecret());

        $this->httpClient->request('POST', $webhook->getUrl(), [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-SolidInvoice-Event' => $message->event,
                'X-SolidInvoice-Signature' => $signature,
            ],
            'body' => $message->payload,
            'timeout' => 10,
        ]);
    }
}
