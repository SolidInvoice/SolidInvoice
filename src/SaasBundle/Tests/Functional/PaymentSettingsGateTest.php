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
use SolidInvoice\PaymentBundle\Action\Settings;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SaaS feature-gate short-circuits the PaymentBundle Settings
 * action with the upgrade banner when `online_payments` is disabled, and
 * lets the page render normally when the feature is enabled or in self-hosted.
 *
 * @group functional
 */
final class PaymentSettingsGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGatedSettingsRendersUpgradeBanner(): void
    {
        $client = $this->bootClient();

        $featureGate = $this->buildFeatureGate(['online_payments' => false]);

        $upgradePromptProvider = $this->createMock(UpgradePromptProvider::class);
        $upgradePromptProvider->method('prompt')
            ->willReturnCallback(static fn (string $key): string => $key === 'online_payments'
                ? '<div class="alert alert-warning"><strong>Upgrade required</strong></div>'
                : '');
        $upgradePromptProvider->method('menuLabel')->willReturn(null);

        self::getContainer()->set(FeatureGate::class, $featureGate);
        self::getContainer()->set(UpgradePromptProvider::class, $upgradePromptProvider);

        $client->request('GET', '/payments/methods');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Upgrade required', (string) $client->getResponse()->getContent());
    }

    public function testUngatedSettingsBypassesBanner(): void
    {
        $client = $this->bootClient();

        $featureGate = $this->buildFeatureGate(['online_payments' => true]);

        $upgradePromptProvider = $this->createMock(UpgradePromptProvider::class);
        $upgradePromptProvider->method('menuLabel')->willReturn(null);
        $upgradePromptProvider->expects(self::never())->method('prompt');

        self::getContainer()->set(FeatureGate::class, $featureGate);
        self::getContainer()->set(UpgradePromptProvider::class, $upgradePromptProvider);

        $client->request('GET', '/payments/methods');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Upgrade required', (string) $client->getResponse()->getContent());
    }

    public function testSelfHostedSettingsBypassesBanner(): void
    {
        $client = $this->bootClient();

        $container = self::getContainer();

        $providerId = 'test.' . UpgradePromptProvider::class;
        self::assertTrue($container->has($providerId));

        if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) !== 'saas') {
            self::assertInstanceOf(NullUpgradePromptProvider::class, $container->get($providerId));
        }

        $client->request('GET', '/payments/methods');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Upgrade required', (string) $client->getResponse()->getContent());
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
