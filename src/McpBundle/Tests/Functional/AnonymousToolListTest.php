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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Security\PublicMcpRequestMatcher;
use stdClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(PublicMcpRequestMatcher::class)]
#[Group('functional')]
final class AnonymousToolListTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testAnonymousClientCanInitializeAndListTools(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $this->rpc($client, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => new stdClass(),
            'clientInfo' => ['name' => 'test', 'version' => '1.0'],
        ]]);
        self::assertResponseIsSuccessful();

        $sessionId = $client->getResponse()->headers->get('Mcp-Session-Id');
        self::assertNotNull($sessionId);

        $this->rpc($client, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $sessionId);
        self::assertResponseIsSuccessful();

        $this->rpc($client, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'], $sessionId);
        self::assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('"tools"', $content);
        self::assertStringContainsString('"name"', $content);
    }

    public function testAnonymousToolCallIsRejected(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $this->rpc($client, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'list_invoices', 'arguments' => []]]);

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHasHeader('WWW-Authenticate');
    }

    public function testBatchSmugglingToolCallIsRejected(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $this->rpc($client, [
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'list_invoices']],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    /**
     * @param array<mixed> $payload
     */
    private function rpc(KernelBrowser $client, array $payload, ?string $sessionId = null): void
    {
        $server = [
            // A non-localhost Host, as in production: the SDK's DNS-rebinding
            // guard 403s anything but localhost unless mcp.http.allowed_hosts is off.
            'HTTP_HOST' => 'app.example.com',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
        ];

        if ($sessionId !== null) {
            $server['HTTP_MCP_SESSION_ID'] = $sessionId;
        }

        $client->request(Request::METHOD_POST, '/_mcp', [], [], $server, json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
