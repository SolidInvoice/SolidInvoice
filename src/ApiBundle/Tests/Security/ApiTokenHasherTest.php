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

namespace SolidInvoice\ApiBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Security\ApiTokenHasher;

final class ApiTokenHasherTest extends TestCase
{
    public function testHashIsDeterministic(): void
    {
        $hasher = new ApiTokenHasher('secret');

        self::assertSame($hasher->hash('plaintext'), $hasher->hash('plaintext'));
    }

    public function testHashDiffersForDifferentSecrets(): void
    {
        $a = new ApiTokenHasher('secret-a');
        $b = new ApiTokenHasher('secret-b');

        self::assertNotSame($a->hash('plaintext'), $b->hash('plaintext'));
    }

    public function testHashDiffersForDifferentInputs(): void
    {
        $hasher = new ApiTokenHasher('secret');

        self::assertNotSame($hasher->hash('one'), $hasher->hash('two'));
    }

    public function testHashIsSha256HexDigest(): void
    {
        $hasher = new ApiTokenHasher('secret');

        $hash = $hasher->hash('plaintext');

        self::assertSame(64, strlen($hash));
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function testHashDoesNotLeakPlaintext(): void
    {
        $hasher = new ApiTokenHasher('secret');

        self::assertNotSame('plaintext', $hasher->hash('plaintext'));
    }
}
