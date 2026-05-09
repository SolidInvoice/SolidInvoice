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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the MCP Authorize action surfaces a friendly upgrade page when
 * the user's eligible companies are all on plans without `mcp_access`. The
 * happy path (gate enabled, valid client, full consent flow) is covered by
 * `\SolidInvoice\McpBundle\Tests\Functional\ConsentGrantTest` and the broader
 * MCP suite — here we only assert the gating edge.
 *
 * @group functional
 */
final class McpAuthorizeGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testRendersUpgradePageWhenMcpAccessFeatureDeniedForAllCompanies(): void
    {
        $client = $this->bootClient();

        $oauthClient = $this->seedOAuthClient();

        $featureGate = $this->createMock(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $key !== 'mcp_access');

        $upgradeProvider = $this->createMock(UpgradePromptProvider::class);
        $upgradeProvider->method('prompt')
            ->willReturnCallback(static fn (string $key): string => $key === 'mcp_access'
                ? '<div class="alert alert-warning"><strong>MCP locked</strong></div>'
                : '');
        $upgradeProvider->method('menuLabel')->willReturn('Business');

        self::getContainer()->set(FeatureGate::class, $featureGate);
        self::getContainer()->set(UpgradePromptProvider::class, $upgradeProvider);

        $client->request('GET', '/oauth/authorize', [
            'response_type' => 'code',
            'client_id' => $oauthClient->getIdentifier(),
            'redirect_uri' => 'http://localhost/cb',
            'scope' => 'mcp:read',
            'state' => 'xyz',
        ]);

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('MCP locked', $body);
    }

    private function seedOAuthClient(): OAuthClient
    {
        $repo = self::getContainer()->get(OAuthClientRepository::class);
        \assert($repo instanceof OAuthClientRepository);

        $client = new OAuthClient();
        $client->setName('Gate test agent');
        $client->setRedirectUris(['http://localhost/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');
        $repo->save($client);

        return $client;
    }

    private function bootClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();
        \assert($user instanceof User);
        $client->loginUser($user);

        return $client;
    }
}
