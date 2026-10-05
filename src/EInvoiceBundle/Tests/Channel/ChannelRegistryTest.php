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

namespace SolidInvoice\EInvoiceBundle\Tests\Channel;

use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Channel\ChannelInterface;
use SolidInvoice\EInvoiceBundle\Channel\ChannelRegistry;
use SolidInvoice\EInvoiceBundle\Exception\UnknownChannelException;
use Symfony\Contracts\Translation\TranslatableInterface;

final class ChannelRegistryTest extends TestCase
{
    public function testGetReturnsRegisteredChannel(): void
    {
        $channel = $this->channel('in-memory');
        $registry = new ChannelRegistry([$channel]);

        self::assertSame($channel, $registry->get('in-memory'));
    }

    public function testGetThrowsForUnknownIdentifier(): void
    {
        $registry = new ChannelRegistry([]);

        $this->expectException(UnknownChannelException::class);

        $registry->get('unknown');
    }

    public function testHasReturnsTrueAndFalse(): void
    {
        $registry = new ChannelRegistry([$this->channel('in-memory')]);

        self::assertTrue($registry->has('in-memory'));
        self::assertFalse($registry->has('storecove'));
    }

    public function testAllPreservesOrder(): void
    {
        $first = $this->channel('in-memory');
        $second = $this->channel('storecove');

        $registry = new ChannelRegistry([$first, $second]);

        self::assertSame([$first, $second], $registry->all());
    }

    private function channel(string $identifier): ChannelInterface
    {
        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getIdentifier')->willReturn($identifier);
        $channel->method('getLabel')->willReturn($this->createStub(TranslatableInterface::class));

        return $channel;
    }
}
