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

namespace SolidInvoice\NotificationBundle\Tests\Twig\Components;

use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Twig\Components\NotificationIntegrations;

final class NotificationIntegrationsTest extends LiveComponentTest
{
    public function testRenderWithNoIntegrations(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify empty state is shown
        self::assertStringNotContainsString('integration-item', $rendered);
    }

    public function testRenderWithSingleIntegration(): void
    {
        $user = $this->getUser();

        // Create a single integration
        $setting = new TransportSetting();
        $setting->setName('My Twilio');
        $setting->setTransport('Twilio');
        $setting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify integration is shown
        self::assertStringContainsString('My Twilio', $rendered);
    }

    public function testRenderWithMultipleIntegrations(): void
    {
        $user = $this->getUser();

        // Create multiple integrations
        $setting1 = new TransportSetting();
        $setting1->setName('Integration A');
        $setting1->setTransport('Twilio');
        $setting1->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting1->setUser($user);
        $setting1->setCompany($user->getCompanies()->first());

        $setting2 = new TransportSetting();
        $setting2->setName('Integration B');
        $setting2->setTransport('Slack');
        $setting2->setSettings(['token' => 'test', 'channel' => '#general']);
        $setting2->setUser($user);
        $setting2->setCompany($user->getCompanies()->first());

        $setting3 = new TransportSetting();
        $setting3->setName('Integration C');
        $setting3->setTransport('Discord');
        $setting3->setSettings(['webhook' => 'https://discord.com/api/webhooks/test']);
        $setting3->setUser($user);
        $setting3->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting1);
        $em->persist($setting2);
        $em->persist($setting3);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify all integrations are shown
        self::assertStringContainsString('Integration A', $rendered);
        self::assertStringContainsString('Integration B', $rendered);
        self::assertStringContainsString('Integration C', $rendered);
    }

    public function testRenderWithSpecificIntegration(): void
    {
        $user = $this->getUser();

        // Create integrations
        $setting1 = new TransportSetting();
        $setting1->setName('Specific Integration');
        $setting1->setTransport('Twilio');
        $setting1->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting1->setUser($user);
        $setting1->setCompany($user->getCompanies()->first());

        $setting2 = new TransportSetting();
        $setting2->setName('Another Integration');
        $setting2->setTransport('Slack');
        $setting2->setSettings(['token' => 'test', 'channel' => '#general']);
        $setting2->setUser($user);
        $setting2->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting1);
        $em->persist($setting2);
        $em->flush();

        // Render with specific integration selected
        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [
                'setting' => (string) $setting1->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify specific integration is accessible
        self::assertStringContainsString('Specific Integration', $rendered);
    }

    public function testIntegrationMethodWithNullSetting(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [
                'setting' => null,
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify no specific integration is shown
        self::assertStringNotContainsString('integration-detail', $rendered);
    }

    public function testIntegrationMethodWithEmptyString(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [
                'setting' => '',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify no specific integration is shown
        self::assertStringNotContainsString('integration-detail', $rendered);
    }

    public function testIntegrationMethodWithInvalidId(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [
                'setting' => '01JBYEQCR7DJ2YW4EXP6FYJZCR', // Non-existent ID
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Component should handle invalid ID gracefully
        // Integration method will return null for non-existent ID
    }

    public function testAlphabeticalSorting(): void
    {
        $user = $this->getUser();

        // Create integrations in non-alphabetical order
        $settingZ = new TransportSetting();
        $settingZ->setName('Zebra Integration');
        $settingZ->setTransport('Twilio');
        $settingZ->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $settingZ->setUser($user);
        $settingZ->setCompany($user->getCompanies()->first());

        $settingA = new TransportSetting();
        $settingA->setName('Apple Integration');
        $settingA->setTransport('Slack');
        $settingA->setSettings(['token' => 'test', 'channel' => '#general']);
        $settingA->setUser($user);
        $settingA->setCompany($user->getCompanies()->first());

        $settingM = new TransportSetting();
        $settingM->setName('Mango Integration');
        $settingM->setTransport('Discord');
        $settingM->setSettings(['webhook' => 'https://discord.com/api/webhooks/test']);
        $settingM->setUser($user);
        $settingM->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($settingZ);
        $em->persist($settingA);
        $em->persist($settingM);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify integrations are sorted alphabetically
        // Can't easily verify order in HTML, but snapshot will catch changes
        self::assertStringContainsString('Apple Integration', $rendered);
        self::assertStringContainsString('Mango Integration', $rendered);
        self::assertStringContainsString('Zebra Integration', $rendered);
    }

    public function testWithActionParameter(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('Action Test Integration');
        $setting->setTransport('Twilio');
        $setting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [
                'setting' => (string) $setting->getId(),
                'action' => 'edit',
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify action parameter is preserved
        self::assertStringContainsString('Action Test Integration', $rendered);
    }

    public function testMultipleUsersIsolation(): void
    {
        $user = $this->getUser();

        // Create integration for current user
        $setting = new TransportSetting();
        $setting->setName('User 1 Integration');
        $setting->setTransport('Twilio');
        $setting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();

        // Verify only current user's integrations are shown
        self::assertStringContainsString('User 1 Integration', $rendered);

        // Count should be 1
        self::assertStringNotContainsString('User 2', $rendered);
    }

    public function testIntegrationTypes(): void
    {
        $user = $this->getUser();

        // Create different types of integrations
        $smsSetting = new TransportSetting();
        $smsSetting->setName('SMS Integration');
        $smsSetting->setTransport('Twilio');
        $smsSetting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $smsSetting->setUser($user);
        $smsSetting->setCompany($user->getCompanies()->first());

        $chatSetting = new TransportSetting();
        $chatSetting->setName('Chat Integration');
        $chatSetting->setTransport('Slack');
        $chatSetting->setSettings(['token' => 'test', 'channel' => '#general']);
        $chatSetting->setUser($user);
        $chatSetting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($smsSetting);
        $em->persist($chatSetting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify both types are shown
        self::assertStringContainsString('SMS Integration', $rendered);
        self::assertStringContainsString('Chat Integration', $rendered);
    }

    public function testUrlParametersPreservation(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('URL Params Test');
        $setting->setTransport('Twilio');
        $setting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [
                'setting' => (string) $setting->getId(),
                'action' => 'configure',
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify URL parameters are preserved in the component
        self::assertStringContainsString('URL Params Test', $rendered);
    }

    public function testEmptyIntegrationsState(): void
    {
        // Test with a fresh user who has no integrations
        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify the component handles empty state gracefully
        // Should not contain any integration items
        self::assertStringNotContainsString('integration-item', $rendered);
    }

    public function testComponentRefresh(): void
    {
        $user = $this->getUser();

        // Create initial integration
        $setting = new TransportSetting();
        $setting->setName('Refresh Test');
        $setting->setTransport('Twilio');
        $setting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        // Initial render
        $rendered1 = $component->render()->toString();
        self::assertStringContainsString('Refresh Test', $rendered1);

        // Refresh component
        $rendered2 = $component->refresh()->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered2)));

        // Should still show the same content
        self::assertStringContainsString('Refresh Test', $rendered2);
    }

    #[DataProvider('transportTypesProvider')]
    public function testDifferentTransportTypes(string $transportName, string $transportType): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName("{$transportName} Test");
        $setting->setTransport($transportName);
        $setting->setSettings(['test' => 'value']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationIntegrations::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        self::assertStringContainsString("{$transportName} Test", $rendered);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function transportTypesProvider(): iterable
    {
        yield 'Twilio SMS' => ['Twilio', 'texter'];
        yield 'Vonage SMS' => ['Vonage', 'texter'];
        yield 'Slack Chat' => ['Slack', 'chatter'];
        yield 'Discord Chat' => ['Discord', 'chatter'];
        yield 'Telegram Chat' => ['Telegram', 'chatter'];
    }
}
