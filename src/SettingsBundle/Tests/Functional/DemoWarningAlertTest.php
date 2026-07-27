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

namespace SolidInvoice\SettingsBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

/**
 * The mail-transport, notification-integration and payment-gateway config forms share the
 * `@SolidInvoiceCore/Demo/_warning_alert.html.twig` partial, guarded by `is_demo()`, so visitors
 * to a demo instance are warned not to enter real/sensitive data. Covers the shared partial via
 * the settings "email" section (the mail-provider block); the notification and payment includes
 * render the exact same partial.
 */
#[Group('functional')]
final class DemoWarningAlertTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string WARNING_TEXT = 'You are in a demo instance.';

    /**
     * @var list<string>
     */
    private array $envOverrides = [];

    public function testWarningAlertIsShownOnMailSettingsInDemoMode(): void
    {
        $this->enableDemoMode();

        $client = $this->bootClient();

        $client->request(Request::METHOD_GET, '/settings', ['section' => 'email']);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(self::WARNING_TEXT, (string) $client->getResponse()->getContent());
    }

    public function testWarningAlertIsNotShownOnMailSettingsOutsideDemoMode(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_GET, '/settings', ['section' => 'email']);

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(self::WARNING_TEXT, (string) $client->getResponse()->getContent());
    }

    #[After]
    public function resetEnvOverrides(): void
    {
        foreach ($this->envOverrides as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
        }

        $this->envOverrides = [];
    }

    private function enableDemoMode(): void
    {
        $this->setEnv('SOLIDINVOICE_MODE', 'demo');
        $this->setEnv('SOLIDINVOICE_DEMO_USERNAME', 'demo@example.com');
        $this->setEnv('SOLIDINVOICE_DEMO_PASSWORD', 'demo-password');
    }

    private function setEnv(string $name, string $value): void
    {
        $_SERVER[$name] = $_ENV[$name] = $value;
        $this->envOverrides[] = $name;
    }

    private function bootClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        return $client;
    }
}
