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

use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Twig\Components\Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use function preg_replace;

/**
 * @covers \SolidInvoice\SettingsBundle\Twig\Components\Settings
 */
final class SettingsTest extends LiveComponentTest
{
    private TestLiveComponent $settingsComponent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsComponent = $this->createLiveComponent(
            name: Settings::class,
            client: $this->client,
        )->actingAs($this->getUser());
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
        $this->settingsComponent->set('settings.id_generation.strategy', 'random_number');
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
}
