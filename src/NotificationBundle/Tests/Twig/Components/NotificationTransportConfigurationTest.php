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

use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use SolidInvoice\NotificationBundle\Twig\Components\NotificationTransportConfiguration;

final class NotificationTransportConfigurationTest extends LiveComponentTest
{
    private TransportSettingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $this->repository = $doctrine->getRepository(TransportSetting::class);
    }

    public function testRenderExistingIntegration(): void
    {
        $user = $this->getUser();

        // Create an existing integration
        $setting = new TransportSetting();
        $setting->setName('My Test Integration');
        $setting->setTransport('FakeSms');
        $setting->setSettings(['test' => 'value']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));

        self::assertStringContainsString('My Test Integration', $rendered);
    }

    public function testShowDeleteConfirmation(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('To Delete');
        $setting->setTransport('FakeSms');
        $setting->setSettings([]);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        // Initially, showDeleteConfirmation should be false
        $rendered = $component->render()->toString();
        self::assertStringNotContainsString('Are you sure you want to delete', $rendered);

        // Call showDeleteConfirmation action
        $component = $component->call('showDeleteConfirmation');

        // After calling, the confirmation should appear
        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
    }

    public function testCancelDelete(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('To Cancel Delete');
        $setting->setTransport('FakeSms');
        $setting->setSettings([]);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        // First show the delete confirmation
        $component = $component->call('showDeleteConfirmation');
        $rendered = $component->render()->toString();
        self::assertStringContainsString('Are you sure you want to delete', $rendered);

        // Now cancel the delete
        $component = $component->call('cancelDelete');

        // After canceling, confirmation should be hidden
        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
        self::assertStringNotContainsString('Are you sure you want to delete', $rendered);
    }

    public function testConfirmDelete(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('To Delete Permanently');
        $setting->setTransport('FakeSms');
        $setting->setSettings([]);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $settingId = $setting->getId();

        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $settingId,
            ],
            client: $this->client,
        )->actingAs($user);

        $component->call('confirmDelete');

        // Verify the integration was deleted
        $deletedSetting = $this->repository->find($settingId);
        self::assertNull($deletedSetting);
    }

    public function testNotificationTypeMappingsSMS(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('SMS Integration');
        $setting->setTransport('FakeSms');
        $setting->setSettings([]);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
        self::assertStringContainsString('SMS', $rendered);
    }

    public function testNotificationTypeMappingsChat(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('Chat Integration');
        $setting->setTransport('Slack');
        $setting->setSettings([]);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
        self::assertStringContainsString('Chat', $rendered);
    }

    public function testLoadingIntegrationByStringId(): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName('Load By ID Test');
        $setting->setTransport('FakeSms');
        $setting->setSettings(['test' => 'value']);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        // Pass setting ID as string (simulating URL parameter)
        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
        self::assertStringContainsString('Load By ID Test', $rendered);
    }

    #[DataProvider('transportTypesProvider')]
    public function testMultipleTransportTypes(string $transportName): void
    {
        $user = $this->getUser();

        $setting = new TransportSetting();
        $setting->setName("{$transportName} Test");
        $setting->setTransport($transportName);
        $setting->setSettings([]);
        $setting->setUser($user);
        $setting->setCompany($user->getCompanies()->first());

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($setting);
        $em->flush();

        $component = $this->createLiveComponent(
            name: NotificationTransportConfiguration::class,
            data: [
                'setting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
        self::assertStringContainsString("{$transportName} Test", $rendered);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function transportTypesProvider(): iterable
    {
        yield 'FakeSms' => ['FakeSms'];
        yield 'Twilio' => ['Twilio'];
        yield 'Slack' => ['Slack'];
        yield 'FakeChat' => ['FakeChat'];
    }
}
