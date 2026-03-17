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

use DateTimeImmutable;
use DateTimeInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\Serializer\SerializerInterface;

final class WebhookPayloadBuilder
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function build(object $entity, string $event): string
    {
        $groups = match (true) {
            $entity instanceof Invoice => ['invoice_api:read'],
            $entity instanceof Quote => ['quote_api:read'],
            default => [],
        };

        return $this->serializer->serialize(
            [
                'event' => $event,
                'data' => $entity,
                'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            ],
            'json',
            $groups !== [] ? ['groups' => $groups] : []
        );
    }
}
