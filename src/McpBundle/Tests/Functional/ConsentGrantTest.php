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
use SolidInvoice\McpBundle\Entity\ConsentGrant;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\OAuth\ConsentService;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\McpBundle\OAuth\ConsentService
 *
 * @group functional
 */
final class ConsentGrantTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testRememberAndLookupConsent(): void
    {
        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();

        $container = self::getContainer();

        $clientRepo = $container->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $clientRepo);

        $client = new OAuthClient();
        $client->setName('Test Agent');
        $client->setRedirectUris(['http://localhost/cb']);
        $client->setGrantTypes(['authorization_code', 'refresh_token']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');
        $clientRepo->save($client);

        $consent = $container->get(ConsentService::class);
        self::assertInstanceOf(ConsentService::class, $consent);

        self::assertFalse($consent->hasPriorConsent($client, $user, $this->company, ['mcp:read']));

        // Without the remember flag, the grant is persisted but hasPriorConsent
        // returns false — the user is prompted again.
        $consent->remember($client, $user, $this->company, ['mcp:read'], remember: false);
        self::assertFalse($consent->hasPriorConsent($client, $user, $this->company, ['mcp:read']));

        // With the remember flag, hasPriorConsent returns true.
        $consent->remember($client, $user, $this->company, ['mcp:read'], remember: true);
        self::assertTrue($consent->hasPriorConsent($client, $user, $this->company, ['mcp:read']));

        // Asking for a scope that wasn't granted must still fail.
        self::assertFalse($consent->hasPriorConsent($client, $user, $this->company, ['mcp:read', 'mcp:write']));
    }

    public function testRememberMergesScopes(): void
    {
        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();

        $container = self::getContainer();

        $clientRepo = $container->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $clientRepo);

        $client = new OAuthClient();
        $client->setName('Scope Merge');
        $client->setRedirectUris(['http://localhost/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read', 'mcp:write']);
        $client->setTokenEndpointAuthMethod('none');
        $clientRepo->save($client);

        $consent = $container->get(ConsentService::class);
        self::assertInstanceOf(ConsentService::class, $consent);

        $consent->remember($client, $user, $this->company, ['mcp:read'], remember: true);
        $consent->remember($client, $user, $this->company, ['mcp:write'], remember: true);

        self::assertTrue($consent->hasPriorConsent($client, $user, $this->company, ['mcp:read', 'mcp:write']));

        // Only one ConsentGrant row should exist (unique client+user+company).
        $registry = self::getContainer()->get('doctrine');
        $grants = $registry->getRepository(ConsentGrant::class)->findBy([
            'client' => $client,
            'user' => $user,
            'company' => $this->company,
        ]);

        self::assertCount(1, $grants);
    }
}
