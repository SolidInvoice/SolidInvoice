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

namespace SolidInvoice\UserBundle\Tests\Action;

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * @group functional
 */
final class AcceptInvitationTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    /**
     * A 26-character string that passes the security access control regex
     * (/invite/accept/[a-zA-Z0-9-]{26}) but is NOT a valid ULID because it
     * contains characters excluded from ULID's Crockford base32 alphabet
     * (e.g. 'O', 'L', 'I' are not valid ULID characters).
     *
     * Before the fix, this caused an unhandled InvalidArgumentException / 500.
     * After the fix, it returns a clean 404.
     */
    public function testReturns404ForInvalidUlidFormat(): void
    {
        // 26 chars (correct length), but uses 'O' and 'L' which are excluded from ULID's
        // Crockford base32 alphabet, so this passes the security regex but fails Ulid::isValid()
        $invalidUlid = '01JXKZ1ABLOOOO1LOOO1LOOOO1';

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request('GET', '/invite/accept/' . $invalidUlid);

        self::assertResponseStatusCodeSame(404);
    }

    public function testReturns404WhenInvitationNotFound(): void
    {
        $validUlid = (string) new Ulid();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request('GET', '/invite/accept/' . $validUlid);

        self::assertResponseStatusCodeSame(404);
    }
}
