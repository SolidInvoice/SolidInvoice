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

namespace SolidInvoice\EInvoiceBundle\Channel;

use SolidInvoice\EInvoiceBundle\Exception\UnknownChannelException;

/**
 * @see \SolidInvoice\EInvoiceBundle\Tests\Channel\ChannelRegistryTest
 */
final readonly class ChannelRegistry
{
    /**
     * @param iterable<ChannelInterface> $channels
     */
    public function __construct(
        private iterable $channels
    ) {
    }

    /**
     * @throws UnknownChannelException
     */
    public function get(string $identifier): ChannelInterface
    {
        foreach ($this->all() as $channel) {
            if ($channel->getIdentifier() === $identifier) {
                return $channel;
            }
        }

        throw UnknownChannelException::forIdentifier($identifier);
    }

    public function has(string $identifier): bool
    {
        return array_any($this->all(), fn (ChannelInterface $channel): bool => $channel->getIdentifier() === $identifier);
    }

    /**
     * @return list<ChannelInterface>
     */
    public function all(): array
    {
        return iterator_to_array($this->channels, false);
    }
}
