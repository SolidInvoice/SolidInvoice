<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Tests\Twig\Components;

use const PASSWORD_DEFAULT;
use PHPUnit\Framework\MockObject\MockObject;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Twig\Components\Settings;
use SolidInvoice\UserBundle\Entity\User;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use function assert;
use function current;
use function password_hash;
use function preg_replace;

/**
 * @covers \SolidInvoice\SettingsBundle\Twig\Components\Settings
 */
final class SettingsTest extends KernelTestCase
{
    use InteractsWithLiveComponents;
    use EnsureApplicationInstalled;
    use MatchesSnapshots;

    private TestLiveComponent $settingsComponent;

    private KernelBrowser $client;

    private MockObject&CsrfTokenManagerInterface $csrfTokenManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSessionIsSet();

        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        self::getContainer()
            ->set('.container.private.security.csrf.token_manager', $this->csrfTokenManager);

        self::getContainer()
            ->set(CsrfTokenManagerInterface::class, $this->csrfTokenManager);

        $this->client = self::getContainer()->get('test.client');
        $this->client->disableReboot();

        $this->settingsComponent = $this->createLiveComponent(
            name: Settings::class,
            client: $this->client,
        )->actingAs($this->getUser());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->client->getKernel()->shutdown();
        $this->client->getKernel()->boot();
    }

    public function testChangeSection(): void
    {
        $this->ensureSessionIsSet();

        $this->settingsComponent->set('section', 'email');
        $this->assertMatchesHtmlSnapshot((string) $this->settingsComponent->render());

        $this->settingsComponent->set('section', 'invoice');
        $this->assertMatchesHtmlSnapshot((string) $this->settingsComponent->render());
    }

    public function testRenderComponent(): void
    {
        $html = $this->settingsComponent->render()->toString();
        $html = preg_replace('/data-content="\d+"/', 'data-content=""', $html);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testSave(): void
    {
        $this->csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true);

        $this->settingsComponent->set('settings.company.currency', 'ZAR');
        $this->settingsComponent->call('save');

        $setting = self::getContainer()
            ->get('doctrine')
            ->getRepository(Setting::class)
            ->findOneBy(['key' => 'system/company/currency']);

        self::assertSame('ZAR', $setting->getValue());

        $response = $this->client->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/settings?section=system', $response->getTargetUrl());

        self::assertSame(
            ['success' => ['settings.saved.success']],
            self::getContainer()->get('session')->getFlashBag()->all()
        );

        $this->assertMatchesHtmlSnapshot((string) $this->settingsComponent->render());
    }

    public function testSaveOnDifferentSection(): void
    {
        $this->csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true);

        $this->settingsComponent->set('section', 'invoice');

        $this->settingsComponent->set('settings.email_subject', 'invoice subject');
        $this->settingsComponent->call('save');

        $setting = self::getContainer()
            ->get('doctrine')
            ->getRepository(Setting::class)
            ->findOneBy(['key' => 'invoice/email_subject']);

        self::assertSame('invoice subject', $setting->getValue());

        $response = $this->client->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/settings?section=invoice', $response->getTargetUrl());

        self::assertSame(
            ['success' => ['settings.saved.success']],
            self::getContainer()->get('session')->getFlashBag()->all()
        );

        $this->assertMatchesHtmlSnapshot((string) $this->settingsComponent->render());

        $this->settingsComponent = $this->createLiveComponent(
            name: Settings::class,
            client: $this->client,
        )->actingAs($this->getUser());
        $this->settingsComponent->set('section', 'invoice');

        $this->assertMatchesHtmlSnapshot((string) $this->settingsComponent->render());
    }

    private function getUser(): User
    {
        $registry = self::getContainer()->get('doctrine');

        $userRepository = $registry->getRepository(User::class);
        $companyRepository = $registry->getRepository(Company::class);

        /** @var User[] $users */
        $users = $userRepository->findAll();

        /** @var Company[] $companies */
        $companies = $companyRepository->findAll();

        if ([] === $users) {
            $user = new User();
            $user->setUsername('test')
                ->setEmail('test@example.com')
                ->setEnabled(true)
                ->setPassword(password_hash('Password1', PASSWORD_DEFAULT));

            foreach ($companies as $company) {
                $user->addCompany($company);
            }

            $registry->getManager()->persist($user);
            $registry->getManager()->flush();
            $users = [$user];
        }

        return current($users);
    }

    protected function ensureSessionIsSet(): void
    {
        $request = new Request();
        $session = self::getContainer()->get('session');
        assert($session instanceof Session);

        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }
}
