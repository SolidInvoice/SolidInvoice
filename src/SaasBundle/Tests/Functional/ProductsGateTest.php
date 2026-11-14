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

/**
 * Asserts that the Products screens are short-circuited with the upgrade page when the
 * `products` feature is disabled, and render the real UI when it is enabled.
 */
#[Group('functional')]
final class ProductsGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    private const string GATED_HEADLINE = 'This feature requires an upgrade';

    public function testGatedIndexRendersUpgradePage(): void
    {
        $client = $this->bootClient(false);

        $client->request(Request::METHOD_GET, '/products/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    public function testGatedAddRendersUpgradePage(): void
    {
        $client = $this->bootClient(false);

        $client->request(Request::METHOD_GET, '/products/add');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    public function testGatedPageStillShowsTheSidebarEntry(): void
    {
        $client = $this->bootClient(false);

        $client->request(Request::METHOD_GET, '/products/');

        self::assertSelectorExists('a[href="/products/"]');
    }

    public function testUngatedIndexBypassesUpgradePage(): void
    {
        $client = $this->bootClient(true);

        $client->request(Request::METHOD_GET, '/products/');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    private function bootClient(bool $productsEnabled): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturnCallback(static fn (string $key): bool => $key !== 'products' || $productsEnabled);
        $featureGate->method('canUse')->willReturnCallback(static fn (string $key): bool => $key !== 'products' || $productsEnabled);
        self::getContainer()->set(FeatureGate::class, $featureGate);

        $user = UserFactory::createOne(['companies' => [$this->company]]);
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        return $client;
    }
}
