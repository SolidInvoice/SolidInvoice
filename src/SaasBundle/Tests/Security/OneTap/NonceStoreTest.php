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

namespace SolidInvoice\SaasBundle\Tests\Security\OneTap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Security\OneTap\NonceStore;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(NonceStore::class)]
final class NonceStoreTest extends TestCase
{
    private NonceStore $store;

    protected function setUp(): void
    {
        $this->store = new NonceStore(new ArrayAdapter(), 300);
    }

    public function testCreateReturnsANonEmptyNonce(): void
    {
        $nonce = $this->store->create();

        self::assertNotSame('', $nonce);
    }

    public function testCreateReturnsUniqueValues(): void
    {
        self::assertNotSame($this->store->create(), $this->store->create());
    }

    public function testAValidNonceCanBeConsumedExactlyOnce(): void
    {
        $nonce = $this->store->create();

        self::assertTrue($this->store->consume($nonce));
        self::assertFalse($this->store->consume($nonce));
    }

    public function testConsumingAnUnknownNonceReturnsFalse(): void
    {
        self::assertFalse($this->store->consume('does-not-exist'));
    }

    public function testConsumingAnEmptyNonceReturnsFalse(): void
    {
        self::assertFalse($this->store->consume(''));
    }

    public function testGetTtlReturnsTheConfiguredLifetime(): void
    {
        self::assertSame(300, $this->store->getTtl());
    }
}
