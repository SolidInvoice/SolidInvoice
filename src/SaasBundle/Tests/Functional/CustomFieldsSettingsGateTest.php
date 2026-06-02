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
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

/**
 * Asserts that the SettingsBundle CustomField management UI is short-circuited
 * with the upgrade banner when the `custom_fields` feature is disabled, and
 * renders the real UI when the feature is enabled.
 */
#[Group('functional')]
final class CustomFieldsSettingsGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string GATED_HEADLINE = 'This feature requires an upgrade';

    public function testGatedIndexRendersUpgradeBanner(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['custom_fields' => false]));

        $client->request(Request::METHOD_GET, '/settings/custom-fields');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    public function testGatedCreateRendersUpgradeBanner(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['custom_fields' => false]));

        $client->request(Request::METHOD_GET, '/settings/custom-fields/new');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    public function testGatedReorderReturnsForbidden(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['custom_fields' => false]));

        $client->request(Request::METHOD_POST, '/settings/custom-fields/reorder', content: '[]');

        self::assertResponseStatusCodeSame(403);
    }

    public function testUngatedIndexBypassesBanner(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['custom_fields' => true]));

        $client->request(Request::METHOD_GET, '/settings/custom-fields');

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
        $featureGate->method('canUse')
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
