<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Notification\Options;

use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class SimpleMessageOptions implements MessageOptionsInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly array $options
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->options;
    }

    public function getRecipientId(): ?string
    {
        return null;
    }
}
