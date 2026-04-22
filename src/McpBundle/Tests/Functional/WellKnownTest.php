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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\McpBundle\Action\WellKnownAuthServer
 * @covers \SolidInvoice\McpBundle\Action\WellKnownProtectedResource
 *
 * @group functional
 */
final class WellKnownTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testAuthorizationServerMetadata(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request('GET', '/.well-known/oauth-authorization-server');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertIsArray($data);
        self::assertArrayHasKey('issuer', $data);
        self::assertArrayHasKey('authorization_endpoint', $data);
        self::assertArrayHasKey('token_endpoint', $data);
        self::assertArrayHasKey('registration_endpoint', $data);
        self::assertSame(['code'], $data['response_types_supported']);
        self::assertSame(['authorization_code', 'refresh_token'], $data['grant_types_supported']);
        self::assertSame(['S256'], $data['code_challenge_methods_supported']);
        self::assertSame(['mcp:read', 'mcp:write'], $data['scopes_supported']);
    }

    public function testProtectedResourceMetadata(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request('GET', '/.well-known/oauth-protected-resource');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertIsArray($data);
        self::assertArrayHasKey('resource', $data);
        self::assertArrayHasKey('authorization_servers', $data);
        self::assertSame(['header'], $data['bearer_methods_supported']);
        self::assertSame(['mcp:read', 'mcp:write'], $data['scopes_supported']);
    }
}
