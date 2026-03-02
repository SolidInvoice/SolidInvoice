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

namespace SolidInvoice\UserBundle\Tests\Functional;

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Entity\UserNotification;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class NotificationPreferencesTest extends WebTestCase
{
    use Factories;
    use EnsureApplicationInstalled;

    public function testNotificationPreferencesPageLoads(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Notification Preferences');
    }

    public function testNotificationPreferencesDisplaysIntegrationManagementCard(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        self::assertSelectorExists('.integration-management-card');
        self::assertSelectorTextContains('.integration-management-title', 'Notification Channels');
    }

    public function testNotificationPreferencesDisplaysAvailableIntegrations(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $transport = new TransportSetting();
        $transport->setName('Slack Integration');
        $transport->setTransport('slack');
        $transport->setUser($user);
        $em->persist($transport);
        $em->flush();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        self::assertSelectorExists('.integration-management-list');
        self::assertSelectorExists('.integration-badge', 'At least one integration badge should exist');

        // Check that we have both Email (always present) and Slack
        $badges = $crawler->filter('.integration-badge');
        self::assertGreaterThanOrEqual(2, $badges->count(), 'Should have at least Email and Slack badges');
    }

    public function testNotificationPreferencesDisplaysNotificationGroups(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        self::assertSelectorExists('.notification-groups');
        self::assertSelectorExists('.notification-group');
        self::assertSelectorExists('.group-title');
    }

    public function testNotificationPreferencesDisplaysNotificationCards(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        self::assertSelectorExists('.notification-card');
        self::assertSelectorExists('.notification-title');
        self::assertSelectorExists('.notification-description');
    }

    public function testNotificationPreferencesContainsEmailCheckboxes(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $userNotification = new UserNotification();
        $userNotification->setEvent('payment_made');
        $userNotification->setUser($user);
        $userNotification->setEmail(false);
        $em->persist($userNotification);
        $em->flush();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        // Check that there are checkboxes for notifications
        self::assertSelectorExists('input[type="checkbox"]');
        self::assertGreaterThan(0, $crawler->filter('input[type="checkbox"]')->count());
    }

    public function testNotificationPreferencesDisplaysChannelSelections(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $transport = new TransportSetting();
        $transport->setName('Slack Integration');
        $transport->setTransport('slack');
        $transport->setUser($user);
        $em->persist($transport);

        $userNotification = new UserNotification();
        $userNotification->setEvent('payment_made');
        $userNotification->setUser($user);
        $userNotification->setEmail(false);
        $em->persist($userNotification);
        $em->flush();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        // Check that channel checkboxes are present
        $channelCheckboxes = $crawler->filter('.notification-card-channels input[type="checkbox"]');
        self::assertGreaterThan(0, $channelCheckboxes->count(), 'Channel checkboxes should be present');
    }

    public function testNotificationPreferencesHasSubmitButton(): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'test@example.com',
        ])->_real();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/profile/notifications');

        // Check that there's a form with a submit button in the notifications actions
        self::assertSelectorExists('.form-page-actions button[type="submit"]');
        self::assertSelectorTextContains('.form-page-actions button', 'Save');
    }
}
