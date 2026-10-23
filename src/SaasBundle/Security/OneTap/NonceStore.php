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

namespace SolidInvoice\SaasBundle\Security\OneTap;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function base64_encode;
use function hash;
use function random_bytes;
use function rtrim;
use function strtr;

/**
 * Issues and burns single-use nonces for the Google One Tap flow.
 *
 * A nonce is created before the widget is initialised and embedded in the
 * returned ID token. On verification the nonce is consumed (deleted), so a
 * captured token cannot be replayed. Backed by a shared cache pool so the
 * single-use guarantee holds across application instances.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Security\OneTap\NonceStoreTest
 */
final readonly class NonceStore
{
    public function __construct(
        #[Autowire(service: 'cache.one_tap_nonce')]
        private CacheItemPoolInterface $cache,
        private int $ttl = 300,
    ) {
    }

    public function create(): string
    {
        $nonce = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $item = $this->cache->getItem($this->key($nonce));
        $item->set(true);
        $item->expiresAfter($this->ttl);

        $this->cache->save($item);

        return $nonce;
    }

    /**
     * Returns true only the first time a valid, unexpired nonce is presented,
     * then permanently invalidates it.
     */
    public function consume(string $nonce): bool
    {
        if ($nonce === '') {
            return false;
        }

        $key = $this->key($nonce);

        if (! $this->cache->hasItem($key)) {
            return false;
        }

        return $this->cache->deleteItem($key);
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    private function key(string $nonce): string
    {
        return 'one_tap_nonce_' . hash('sha256', $nonce);
    }
}
