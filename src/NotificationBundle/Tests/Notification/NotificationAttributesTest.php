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

namespace SolidInvoice\NotificationBundle\Tests\Notification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Notification\ClientCreateNotification;
use SolidInvoice\InvoiceBundle\Notification\InvoiceStatusNotification;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;
use SolidInvoice\PaymentBundle\Notification\PaymentReceivedNotification;
use SolidInvoice\QuoteBundle\Notification\QuoteStatusNotification;

/**
 * @covers \SolidInvoice\ClientBundle\Notification\ClientCreateNotification
 * @covers \SolidInvoice\InvoiceBundle\Notification\InvoiceStatusNotification
 * @covers \SolidInvoice\PaymentBundle\Notification\PaymentReceivedNotification
 * @covers \SolidInvoice\QuoteBundle\Notification\QuoteStatusNotification
 */
final class NotificationAttributesTest extends TestCase
{
    #[DataProvider('notificationClassProvider')]
    public function testNotificationHasAsNotificationAttribute(string $class): void
    {
        $reflection = new \ReflectionClass($class);
        $attributes = $reflection->getAttributes(AsNotification::class);

        self::assertCount(1, $attributes, sprintf('Class %s must have exactly one AsNotification attribute', $class));
    }

    #[DataProvider('notificationClassProvider')]
    public function testNotificationAttributeHasName(string $class): void
    {
        $attribute = $this->getNotificationAttribute($class);

        self::assertNotEmpty($attribute->name, sprintf('Class %s must have a non-empty name', $class));
    }

    #[DataProvider('notificationClassProvider')]
    public function testNotificationAttributeHasTitle(string $class): void
    {
        $attribute = $this->getNotificationAttribute($class);

        self::assertNotEmpty($attribute->title, sprintf('Class %s must have a non-empty title', $class));
    }

    #[DataProvider('notificationClassProvider')]
    public function testNotificationAttributeHasDescription(string $class): void
    {
        $attribute = $this->getNotificationAttribute($class);

        self::assertNotEmpty($attribute->description, sprintf('Class %s must have a non-empty description', $class));
    }

    #[DataProvider('notificationClassProvider')]
    public function testNotificationAttributeHasIcon(string $class): void
    {
        $attribute = $this->getNotificationAttribute($class);

        self::assertNotEmpty($attribute->icon, sprintf('Class %s must have a non-empty icon', $class));
        self::assertStringStartsWith('tabler:', $attribute->icon, sprintf('Icon for %s must be a Tabler icon', $class));
    }

    #[DataProvider('notificationClassProvider')]
    public function testNotificationAttributeHasCategory(string $class): void
    {
        $attribute = $this->getNotificationAttribute($class);

        self::assertInstanceOf(
            NotificationCategory::class,
            $attribute->category,
            sprintf('Class %s must have a valid NotificationCategory', $class)
        );
    }

    public function testClientCreateNotificationMetadata(): void
    {
        $attribute = $this->getNotificationAttribute(ClientCreateNotification::class);

        self::assertSame('client_create', $attribute->name);
        self::assertSame('Client Created', $attribute->title);
        self::assertSame('When a new client is added to your account', $attribute->description);
        self::assertSame('tabler:user-plus', $attribute->icon);
        self::assertSame(NotificationCategory::CLIENT, $attribute->category);
    }

    public function testInvoiceStatusNotificationMetadata(): void
    {
        $attribute = $this->getNotificationAttribute(InvoiceStatusNotification::class);

        self::assertSame('invoice_status_update', $attribute->name);
        self::assertSame('Invoice Status Changed', $attribute->title);
        self::assertSame('When an invoice status changes (draft, sent, paid, etc.)', $attribute->description);
        self::assertSame('tabler:file-invoice', $attribute->icon);
        self::assertSame(NotificationCategory::INVOICE, $attribute->category);
    }

    public function testPaymentReceivedNotificationMetadata(): void
    {
        $attribute = $this->getNotificationAttribute(PaymentReceivedNotification::class);

        self::assertSame('payment_made', $attribute->name);
        self::assertSame('Payment Received', $attribute->title);
        self::assertSame('When a payment is received for an invoice', $attribute->description);
        self::assertSame('tabler:cash', $attribute->icon);
        self::assertSame(NotificationCategory::PAYMENT, $attribute->category);
    }

    public function testQuoteStatusNotificationMetadata(): void
    {
        $attribute = $this->getNotificationAttribute(QuoteStatusNotification::class);

        self::assertSame('quote_status_update', $attribute->name);
        self::assertSame('Quote Status Changed', $attribute->title);
        self::assertSame('When a quote status changes (draft, sent, accepted, etc.)', $attribute->description);
        self::assertSame('tabler:file-text', $attribute->icon);
        self::assertSame(NotificationCategory::QUOTE, $attribute->category);
    }

    /**
     * @return iterable<string, array<string>>
     */
    public static function notificationClassProvider(): iterable
    {
        yield 'ClientCreateNotification' => [ClientCreateNotification::class];
        yield 'InvoiceStatusNotification' => [InvoiceStatusNotification::class];
        yield 'PaymentReceivedNotification' => [PaymentReceivedNotification::class];
        yield 'QuoteStatusNotification' => [QuoteStatusNotification::class];
    }

    private function getNotificationAttribute(string $class): AsNotification
    {
        $reflection = new \ReflectionClass($class);
        $attributes = $reflection->getAttributes(AsNotification::class);

        self::assertCount(1, $attributes);

        return $attributes[0]->newInstance();
    }
}
