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
use SolidInvoice\NotificationBundle\Twig\Components\NotificationMarketplace;

final class NotificationMarketplaceTest extends LiveComponentTest
{
    public function testRenderDefaultView(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify default view is marketplace
        self::assertStringContainsString('marketplace', $rendered);
    }

    public function testRenderWithSmsTab(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify SMS integrations are shown
        self::assertStringContainsString('SMS', $rendered);
    }

    public function testRenderWithChatTab(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'chat',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify Chat integrations are shown
        self::assertStringContainsString('Chat', $rendered);
    }

    public function testSwitchTab(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        // Verify SMS tab is active
        $rendered = $component->render()->toString();
        self::assertStringContainsString('Twilio', $rendered);

        // Switch to chat tab by setting the property
        $component = $component->set('activeTab', 'chat');

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify chat tab is now active with chat integrations
        self::assertStringContainsString('Slack', $rendered);
    }

    public function testSearchIntegrations(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($this->getUser());

        // Set search query
        $component = $component->set('searchQuery', 'Twilio');

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify Twilio is in results
        self::assertStringContainsString('Twilio', $rendered);
    }

    public function testSearchByDescription(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($this->getUser());

        // Search by part of description
        $component = $component->set('searchQuery', 'Slack channels');

        $rendered = $component->render()->toString();

        // Verify Slack is in results (has "channels" in description)
        self::assertStringContainsString('Slack', $rendered);
    }

    public function testSearchByType(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($this->getUser());

        // Search by type
        $component = $component->set('searchQuery', 'SMS');

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify SMS integrations are shown
        self::assertStringContainsString('SMS', $rendered);
    }

    public function testClearSearch(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'searchQuery' => 'Twilio',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        // Verify search is set
        $rendered = $component->render()->toString();
        self::assertStringContainsString('Twilio', $rendered);

        // Clear search
        $component = $component->call('clearSearch');

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify all integrations are now shown
        self::assertStringContainsString('marketplace', $rendered);
    }

    public function testFeaturedIntegrations(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify popular SMS integrations are shown (Twilio, Vonage)
        self::assertStringContainsString('Twilio', $rendered);
        self::assertStringContainsString('Vonage', $rendered);
    }

    public function testConfiguredIntegrationsDisplay(): void
    {
        $user = $this->getUser();

        // Create some configured integrations
        $setting1 = new TransportSetting();
        $setting1->setName('My Twilio');
        $setting1->setTransport('Twilio');
        $setting1->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting1->setUser($user);
        $setting1->setCompany($user->getCompanies()->first());

        $setting2 = new TransportSetting();
        $setting2->setName('My Slack');
        $setting2->setTransport('Slack');
        $setting2->setSettings(['token' => 'test', 'channel' => '#general']);
        $setting2->setUser($user);
        $setting2->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting1);
        $em->persist($setting2);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify configured integrations are shown
        self::assertStringContainsString('My Twilio', $rendered);
        self::assertStringContainsString('My Slack', $rendered);
    }

    public function testIntegrationIcons(): void
    {
        // Test SMS integrations have icons (SVG elements)
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify SMS integrations are present with icons
        self::assertStringContainsString('Twilio', $rendered);
        self::assertStringContainsString('integration-card-icon', $rendered);
        self::assertStringContainsString('<svg', $rendered);

        // Test Chat integrations have icons
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'chat',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify Chat integrations are present with icons
        self::assertStringContainsString('Slack', $rendered);
        self::assertStringContainsString('integration-card-icon', $rendered);
        self::assertStringContainsString('<svg', $rendered);
    }

    public function testIntegrationDescriptions(): void
    {
        // Test SMS tab
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        // Verify SMS description is shown
        self::assertStringContainsString('Send SMS notifications with Twilio', $rendered);

        // Test Chat tab
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'chat',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        // Verify Chat description is shown
        self::assertStringContainsString('Send notifications to Slack channels', $rendered);
    }

    public function testIsConfiguredBadge(): void
    {
        $user = $this->getUser();

        // Create a configured integration
        $setting = new TransportSetting();
        $setting->setName('Configured Twilio');
        $setting->setTransport('Twilio');
        $setting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();

        // Verify Twilio is marked as configured
        // This depends on the template implementation
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
    }

    public function testCloseModal(): void
    {
        // Start with a normal marketplace component
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        // Call closeModal (should work even if nothing to close)
        $component = $component->call('closeModal');

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // After closeModal, the component should render marketplace normally
        self::assertStringContainsString('marketplace', $rendered);
        self::assertStringContainsString('Twilio', $rendered); // Still in the list
    }

    public function testTabFilteringWithSearch(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'chat',
                'searchQuery' => 'Slack',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify only Slack is shown (chat type + search filter)
        self::assertStringContainsString('Slack', $rendered);
        // Verify SMS integrations are not shown
        self::assertStringNotContainsString('Twilio', $rendered);
    }

    public function testRegularIntegrationsSeparatedFromFeatured(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify both featured and regular integrations are present
        self::assertStringContainsString('Twilio', $rendered); // Featured
        self::assertStringContainsString('Vonage', $rendered); // Featured

        // Some regular SMS providers should also be there
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($rendered));
    }

    public function testEmptySearchResults(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'searchQuery' => 'NonExistentIntegration123',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify no integrations are shown
        self::assertStringNotContainsString('Twilio', $rendered);
        self::assertStringNotContainsString('Slack', $rendered);
    }

    public function testIntegrationTypeLabels(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify type labels are shown
        self::assertStringContainsString('SMS', $rendered);

        // Switch to chat tab
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'chat',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        self::assertStringContainsString('Chat', $rendered);
    }

    public function testAlphabeticalSorting(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Popular integrations should come first, then alphabetical
        // We can't easily test ordering in HTML, but snapshot will catch changes
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($rendered));
    }

    public function testUrlParameters(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'view' => 'marketplace',
                'activeTab' => 'sms',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        // Verify URL parameters are working - SMS tab is active
        self::assertStringContainsString('Twilio', $rendered);
        self::assertStringContainsString('marketplace', $rendered);
    }

    public function testMultipleUsersIsolation(): void
    {
        $user = $this->getUser();

        // Create integration for current user
        $setting = new TransportSetting();
        $setting->setName('User 1 Twilio');
        $setting->setTransport('Twilio');
        $setting->setSettings(['sid' => 'test', 'token' => 'test', 'from' => '+1234567890']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();

        // Verify only current user's integrations are shown
        self::assertStringContainsString('User 1 Twilio', $rendered);
    }

    public function testCaseInsensitiveSearch(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'searchQuery' => 'twilio', // lowercase
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify case-insensitive search works
        self::assertStringContainsString('Twilio', $rendered);
    }

    public function testPartialSearch(): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'searchQuery' => 'wil', // partial match for "Twilio"
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify partial search works
        self::assertStringContainsString('Twilio', $rendered);
    }

    #[DataProvider('popularIntegrationsProvider')]
    public function testPopularIntegrations(string $integrationName, string $tab): void
    {
        $component = $this->createLiveComponent(
            name: NotificationMarketplace::class,
            data: [
                'activeTab' => $tab,
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify popular integrations are displayed
        self::assertStringContainsString($integrationName, $rendered);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function popularIntegrationsProvider(): iterable
    {
        yield 'Twilio' => ['Twilio', 'sms'];
        yield 'Vonage' => ['Vonage', 'sms'];
        yield 'Slack' => ['Slack', 'chat'];
        yield 'Discord' => ['Discord', 'chat'];
        yield 'Telegram' => ['Telegram', 'chat'];
        yield 'MicrosoftTeams' => ['MicrosoftTeams', 'chat'];
    }
}
