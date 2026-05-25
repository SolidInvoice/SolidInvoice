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

namespace SolidInvoice\ApiBundle\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hashes API tokens with HMAC-SHA256 keyed by the application secret.
 *
 * Why HMAC and not bcrypt/argon2: authentication needs a deterministic
 * equality lookup on an indexed column; per-row salts would force a full
 * table scan. Token entropy is already 256 bits of random_bytes, so a
 * pepper keyed by the app secret is sufficient. A DB-only leak yields
 * no usable hashes without also having the secret.
 * @see \SolidInvoice\ApiBundle\Tests\Security\ApiTokenHasherTest
 */
final readonly class ApiTokenHasher
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        private string $appSecret,
    ) {
    }

    public function hash(string $plaintextToken): string
    {
        return hash_hmac('sha256', $plaintextToken, $this->appSecret);
    }
}
