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

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Feature\NullUpgradePromptProvider;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SaaS feature-gate short-circuits the PaymentBundle Settings
 * action with the upgrade banner when `online_payments` is disabled, and
 * lets the page render normally when the feature is enabled or in self-hosted.
 */
#[Group('functional')]
final class PaymentSettingsGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string GATED_HEADLINE = 'This feature requires an upgrade';

    public function testGatedSettingsRendersUpgradeBanner(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['online_payments' => false]));

        $client->request(Request::METHOD_GET, '/payments/methods');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    public function testUngatedSettingsBypassesBanner(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['online_payments' => true]));

        $client->request(Request::METHOD_GET, '/payments/methods');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
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

        $client->request(Request::METHOD_GET, '/payments/methods');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    /**
     * @param array<string, bool> $overrides
     */
    private function buildFeatureGate(array $overrides): FeatureGate
    {
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $overrides[$key] ?? true);

        return $featureGate;
    }

    private function bootClient(?FeatureGate $featureGate = null): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        if ($featureGate instanceof FeatureGate) {
            self::getContainer()->set(FeatureGate::class, $featureGate);
        }

        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        return $client;
    }
}
