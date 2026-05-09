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

use SolidInvoice\CoreBundle\Feature\NullUpgradePromptProvider;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SaaS feature-gate short-circuits the UserBundle ApiIndex action
 * with the upgrade banner when the `rest_api_access` feature is disabled, and
 * lets the API token UI render normally when the feature is enabled or in
 * self-hosted mode.
 *
 * @group functional
 */
final class ApiTokenCreateGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGatedIndexRendersUpgradeBanner(): void
    {
        $client = $this->bootClient();

        $featureGate = $this->buildFeatureGate(['rest_api_access' => false]);

        $upgradePromptProvider = $this->createMock(UpgradePromptProvider::class);
        $upgradePromptProvider->method('prompt')
            ->willReturnCallback(static fn (string $key): string => $key === 'rest_api_access'
                ? '<div class="alert alert-warning"><strong>API access locked</strong></div>'
                : '');
        $upgradePromptProvider->method('menuLabel')->willReturn(null);

        self::getContainer()->set(FeatureGate::class, $featureGate);
        self::getContainer()->set(UpgradePromptProvider::class, $upgradePromptProvider);

        $client->request('GET', '/profile/api');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('API access locked', $body);
        // Confirm the live component (and its create-button) is NOT in the gated page
        self::assertStringNotContainsString('id="api-token-', $body);
    }

    public function testUngatedIndexRendersTokenList(): void
    {
        $client = $this->bootClient();

        $featureGate = $this->buildFeatureGate(['rest_api_access' => true]);

        $upgradePromptProvider = $this->createMock(UpgradePromptProvider::class);
        $upgradePromptProvider->method('menuLabel')->willReturn(null);
        $upgradePromptProvider->expects(self::never())->method('prompt');

        self::getContainer()->set(FeatureGate::class, $featureGate);
        self::getContainer()->set(UpgradePromptProvider::class, $upgradePromptProvider);

        $client->request('GET', '/profile/api');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringNotContainsString('API access locked', $body);
    }

    public function testSelfHostedIndexRendersTokenList(): void
    {
        $client = $this->bootClient();

        $container = self::getContainer();

        $providerId = 'test.' . UpgradePromptProvider::class;
        self::assertTrue($container->has($providerId));

        if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) !== 'saas') {
            self::assertInstanceOf(NullUpgradePromptProvider::class, $container->get($providerId));
        }

        $client->request('GET', '/profile/api');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('API access locked', (string) $client->getResponse()->getContent());
    }

    /**
     * @param array<string, bool> $overrides
     */
    private function buildFeatureGate(array $overrides): FeatureGate
    {
        $featureGate = $this->createMock(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $overrides[$key] ?? true);

        return $featureGate;
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
