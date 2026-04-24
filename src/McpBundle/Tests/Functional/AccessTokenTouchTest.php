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

use DateTimeImmutable;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\McpBundle\Repository\McpAccessTokenRepository::touch
 *
 * @group functional
 */
final class AccessTokenTouchTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testTouchUpdatesLastUsedAt(): void
    {
        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();
        self::assertInstanceOf(User::class, $user);

        $container = self::getContainer();

        $clientRepo = $container->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $clientRepo);

        $client = new OAuthClient();
        $client->setName('Touch Test');
        $client->setRedirectUris(['http://localhost/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');
        $clientRepo->save($client);

        $accessTokenRepo = $container->get(McpAccessTokenRepository::class);
        self::assertInstanceOf(McpAccessTokenRepository::class, $accessTokenRepo);

        $token = new McpAccessToken();
        $token->setOAuthClient($client);
        $token->setUser($user);
        $token->setCompany($this->company);
        $token->setIdentifier('jti-test-' . bin2hex(random_bytes(8)));
        $token->setScopeValues(['mcp:read']);
        $token->setExpiresAt(new DateTimeImmutable('+1 hour'));

        $accessTokenRepo->persistNewAccessToken($token);

        self::assertNull($token->getLastUsedAt());

        $beforeTouch = new DateTimeImmutable('-1 second');
        $accessTokenRepo->touch($token);

        // Round-trip: reload from DB to verify the UPDATE committed.
        $em = $container->get('doctrine')->getManager();
        $em->clear();

        $reloaded = $accessTokenRepo->findByJti($token->getJti());
        self::assertInstanceOf(McpAccessToken::class, $reloaded);
        $lastUsedAt = $reloaded->getLastUsedAt();
        self::assertNotNull($lastUsedAt);
        // Bound below by pre-touch time to catch silently-stale writes,
        // and above by now+1s to catch clock drift / future timestamps.
        self::assertGreaterThanOrEqual($beforeTouch, $lastUsedAt);
        self::assertLessThanOrEqual(new DateTimeImmutable('+1 second'), $lastUsedAt);
    }
}
