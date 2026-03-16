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
use Symfony\Component\Serializer\SerializerInterface;

final class WebhookPayloadBuilder
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function build(object $entity, string $event): string
    {
        return $this->serializer->serialize(
            [
                'event' => $event,
                'data' => $entity,
                'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            ],
            'json'
        );
    }
}
