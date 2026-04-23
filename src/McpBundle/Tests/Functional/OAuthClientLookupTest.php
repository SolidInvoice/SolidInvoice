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

namespace SolidInvoice\McpBundle\Tests\Functional;

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Regression: league/oauth2-server passes the stored client_id back to the
 * repository. We store as ULID but `getIdentifier()` returns RFC 4122 UUID
 * form, so lookups must accept both.
 *
 * @covers \SolidInvoice\McpBundle\Repository\OAuthClientRepository::getClientEntity
 *
 * @group functional
 */
final class OAuthClientLookupTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGetClientEntityAcceptsRfc4122Form(): void
    {
        $repo = self::getContainer()->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $repo);

        $client = new OAuthClient();
        $client->setName('Lookup Test');
        $client->setRedirectUris(['http://localhost/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');
        $repo->save($client);

        // Crockford base32 form (26 chars)
        $crockford = (string) $client->getId();
        // RFC 4122 UUID form (36 chars with hyphens) — what getIdentifier() emits
        $rfc4122 = $client->getIdentifier();

        self::assertNotSame($crockford, $rfc4122);

        $byCrockford = $repo->getClientEntity($crockford);
        $byRfc4122 = $repo->getClientEntity($rfc4122);

        self::assertInstanceOf(OAuthClient::class, $byCrockford);
        self::assertInstanceOf(OAuthClient::class, $byRfc4122);
        self::assertSame($client->getId()?->toRfc4122(), $byCrockford->getId()?->toRfc4122());
        self::assertSame($client->getId()?->toRfc4122(), $byRfc4122->getId()?->toRfc4122());
    }

    public function testGetClientEntityReturnsNullForGarbage(): void
    {
        $repo = self::getContainer()->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $repo);

        self::assertNull($repo->getClientEntity('not-a-ulid'));
        self::assertNull($repo->getClientEntity(''));
    }
}
