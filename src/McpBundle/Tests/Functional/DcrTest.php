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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\McpBundle\Action\DynamicClientRegistration
 *
 * @group functional
 */
final class DcrTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testRegisterPublicClient(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request(
            'POST',
            '/oauth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'client_name' => 'Claude Desktop Test',
                'redirect_uris' => ['http://localhost:33418/callback'],
                'grant_types' => ['authorization_code', 'refresh_token'],
                'token_endpoint_auth_method' => 'none',
                'scope' => 'mcp:read mcp:write',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertIsArray($data);
        self::assertArrayHasKey('client_id', $data);
        self::assertArrayNotHasKey('client_secret', $data);
        self::assertSame('Claude Desktop Test', $data['client_name']);
        self::assertSame(['http://localhost:33418/callback'], $data['redirect_uris']);
        self::assertSame('none', $data['token_endpoint_auth_method']);

        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);

        $oauthClient = $registry->getRepository(OAuthClient::class)->find(Ulid::fromString($data['client_id']));
        self::assertInstanceOf(OAuthClient::class, $oauthClient);
        self::assertSame('Claude Desktop Test', $oauthClient->getName());
    }

    public function testRegisterConfidentialClientReturnsSecret(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request(
            'POST',
            '/oauth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'client_name' => 'Confidential Agent',
                'redirect_uris' => ['https://example.com/cb'],
                'token_endpoint_auth_method' => 'client_secret_basic',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayHasKey('client_secret', $data);
        self::assertIsString($data['client_secret']);
    }

    public function testRegisterRejectsMissingRedirectUri(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request(
            'POST',
            '/oauth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['client_name' => 'Missing URIs'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('invalid_redirect_uri', $data['error']);
    }

    public function testRegisterRejectsInvalidRedirectUri(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request(
            'POST',
            '/oauth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'client_name' => 'Bad URI',
                'redirect_uris' => ['not-a-url'],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterRejectsUnsupportedGrantType(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request(
            'POST',
            '/oauth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'client_name' => 'Bad Grants',
                'redirect_uris' => ['https://example.com/cb'],
                'grant_types' => ['client_credentials'],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
