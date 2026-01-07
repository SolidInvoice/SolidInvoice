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

namespace SolidInvoice\UserBundle\Tests\Twig\Components;

use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;
use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use SolidInvoice\NotificationBundle\Repository\UserNotificationRepository;
use SolidInvoice\UserBundle\Twig\Components\UserNotification;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;

/**
 * @covers \SolidInvoice\UserBundle\Twig\Components\UserNotification
 */
final class UserNotificationTest extends TestCase
{
    use EnsureApplicationInstalled;

    private UserNotification $component;

    private UserNotificationRepository $userNotificationRepository;

    private TransportSettingRepository $transportSettingRepository;

    /**
     * @var ServiceLocator<NotificationMessage>
     */
    private ServiceLocator $notificationLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userNotificationRepository = static::getContainer()->get(UserNotificationRepository::class);
        $this->transportSettingRepository = static::getContainer()->get(TransportSettingRepository::class);

        $clientNotification = new #[AsNotification(name: 'client_created', title: 'Client Created', description: 'When a new client is added to your account', icon: 'tabler:user-plus', category: NotificationCategory::CLIENT, )] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $invoiceNotification = new #[AsNotification(name: 'invoice_status_changed', title: 'Invoice Status Changed', description: 'When an invoice status changes (draft, sent, paid, etc.)', icon: 'tabler:file-invoice', category: NotificationCategory::INVOICE, )] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $paymentNotification = new #[AsNotification(name: 'payment_made', title: 'Payment Received', description: 'When a payment is received for an invoice', icon: 'tabler:cash', category: NotificationCategory::PAYMENT, )] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $quoteNotification = new #[AsNotification(name: 'quote_status_changed', title: 'Quote Status Changed', description: 'When a quote status changes (draft, sent, accepted, etc.)', icon: 'tabler:file-text', category: NotificationCategory::QUOTE, )] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $this->notificationLocator = new ServiceLocator([
            'client_created' => static fn () => $clientNotification,
            'invoice_status_changed' => static fn () => $invoiceNotification,
            'payment_made' => static fn () => $paymentNotification,
            'quote_status_changed' => static fn () => $quoteNotification,
        ]);

        $this->component = new UserNotification(
            $this->userNotificationRepository,
            $this->transportSettingRepository,
            $this->notificationLocator,
        );
    }

    public function testGroupNotificationsByCategory(): void
    {
        $grouped = $this->component->groupNotificationsByCategory();

        self::assertIsArray($grouped);
        self::assertArrayHasKey('Client Notifications', $grouped);
        self::assertArrayHasKey('Invoice Notifications', $grouped);
        self::assertArrayHasKey('Payment Notifications', $grouped);
        self::assertArrayHasKey('Quote Notifications', $grouped);

        self::assertContains('client_created', $grouped['Client Notifications']);
        self::assertContains('invoice_status_changed', $grouped['Invoice Notifications']);
        self::assertContains('payment_made', $grouped['Payment Notifications']);
        self::assertContains('quote_status_changed', $grouped['Quote Notifications']);
    }

    public function testGetEventTitle(): void
    {
        self::assertSame('Client Created', $this->component->getEventTitle('client_created'));
        self::assertSame('Invoice Status Changed', $this->component->getEventTitle('invoice_status_changed'));
        self::assertSame('Payment Received', $this->component->getEventTitle('payment_made'));
        self::assertSame('Quote Status Changed', $this->component->getEventTitle('quote_status_changed'));
    }

    public function testGetEventTitleFallback(): void
    {
        $locator = new ServiceLocator([]);
        $component = new UserNotification(
            $this->userNotificationRepository,
            $this->transportSettingRepository,
            $locator,
        );

        self::assertSame('Unknown Event', $component->getEventTitle('unknown_event'));
    }

    public function testGetEventDescription(): void
    {
        self::assertSame('When a new client is added to your account', $this->component->getEventDescription('client_created'));
        self::assertSame('When an invoice status changes (draft, sent, paid, etc.)', $this->component->getEventDescription('invoice_status_changed'));
        self::assertSame('When a payment is received for an invoice', $this->component->getEventDescription('payment_made'));
        self::assertSame('When a quote status changes (draft, sent, accepted, etc.)', $this->component->getEventDescription('quote_status_changed'));
    }

    public function testGetEventDescriptionFallback(): void
    {
        $locator = new ServiceLocator([]);
        $component = new UserNotification(
            $this->userNotificationRepository,
            $this->transportSettingRepository,
            $locator,
        );

        self::assertSame('', $component->getEventDescription('unknown_event'));
    }

    public function testGetEventIcon(): void
    {
        self::assertSame('tabler:user-plus', $this->component->getEventIcon('client_created'));
        self::assertSame('tabler:file-invoice', $this->component->getEventIcon('invoice_status_changed'));
        self::assertSame('tabler:cash', $this->component->getEventIcon('payment_made'));
        self::assertSame('tabler:file-text', $this->component->getEventIcon('quote_status_changed'));
    }

    public function testGetEventIconFallback(): void
    {
        $locator = new ServiceLocator([]);
        $component = new UserNotification(
            $this->userNotificationRepository,
            $this->transportSettingRepository,
            $locator,
        );

        self::assertSame('tabler:bell', $component->getEventIcon('unknown_event'));
    }

    public function testGetCategoryIcon(): void
    {
        self::assertSame('tabler:users', $this->component->getCategoryIcon('Client Notifications'));
        self::assertSame('tabler:file-invoice', $this->component->getCategoryIcon('Invoice Notifications'));
        self::assertSame('tabler:credit-card', $this->component->getCategoryIcon('Payment Notifications'));
        self::assertSame('tabler:file-text', $this->component->getCategoryIcon('Quote Notifications'));
    }

    public function testGetCategoryIconFallback(): void
    {
        self::assertSame('tabler:bell', $this->component->getCategoryIcon('Unknown Category'));
    }

    public function testGetTransportIcon(): void
    {
        self::assertSame('tabler:brand-slack', $this->component->getTransportIcon('slack'));
        self::assertSame('tabler:brand-discord', $this->component->getTransportIcon('discord'));
        self::assertSame('tabler:brand-telegram', $this->component->getTransportIcon('telegram'));
        self::assertSame('tabler:brand-teams', $this->component->getTransportIcon('teams'));
        self::assertSame('tabler:message', $this->component->getTransportIcon('twilio'));
        self::assertSame('tabler:message', $this->component->getTransportIcon('sms'));
        self::assertSame('tabler:message', $this->component->getTransportIcon('vonage'));
        self::assertSame('tabler:message', $this->component->getTransportIcon('messagebird'));
        self::assertSame('tabler:bell', $this->component->getTransportIcon('unknown'));
    }

    public function testGetTransportIconCaseInsensitive(): void
    {
        self::assertSame('tabler:brand-slack', $this->component->getTransportIcon('SLACK'));
        self::assertSame('tabler:brand-slack', $this->component->getTransportIcon('Slack'));
        self::assertSame('tabler:brand-slack', $this->component->getTransportIcon('SlackTransport'));
    }
}
