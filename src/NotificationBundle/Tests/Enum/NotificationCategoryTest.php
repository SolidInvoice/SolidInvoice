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

namespace SolidInvoice\NotificationBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;

/**
 * @covers \SolidInvoice\NotificationBundle\Enum\NotificationCategory
 */
final class NotificationCategoryTest extends TestCase
{
    public function testGetLabel(): void
    {
        self::assertSame('Client Notifications', NotificationCategory::CLIENT->getLabel());
        self::assertSame('Invoice Notifications', NotificationCategory::INVOICE->getLabel());
        self::assertSame('Payment Notifications', NotificationCategory::PAYMENT->getLabel());
        self::assertSame('Quote Notifications', NotificationCategory::QUOTE->getLabel());
        self::assertSame('Other Notifications', NotificationCategory::OTHER->getLabel());
    }

    public function testGetIcon(): void
    {
        self::assertSame('tabler:users', NotificationCategory::CLIENT->getIcon());
        self::assertSame('tabler:file-invoice', NotificationCategory::INVOICE->getIcon());
        self::assertSame('tabler:credit-card', NotificationCategory::PAYMENT->getIcon());
        self::assertSame('tabler:file-text', NotificationCategory::QUOTE->getIcon());
        self::assertSame('tabler:bell', NotificationCategory::OTHER->getIcon());
    }

    public function testAllCases(): void
    {
        $cases = NotificationCategory::cases();

        self::assertCount(5, $cases);
        self::assertContains(NotificationCategory::CLIENT, $cases);
        self::assertContains(NotificationCategory::INVOICE, $cases);
        self::assertContains(NotificationCategory::PAYMENT, $cases);
        self::assertContains(NotificationCategory::QUOTE, $cases);
        self::assertContains(NotificationCategory::OTHER, $cases);
    }

    public function testEachCategoryHasUniqueLabel(): void
    {
        $labels = array_map(static fn (NotificationCategory $category) => $category->getLabel(), NotificationCategory::cases());

        $uniqueLabels = array_unique($labels);
        self::assertCount(count($labels), $uniqueLabels);
    }

    public function testEachCategoryHasIcon(): void
    {
        foreach (NotificationCategory::cases() as $category) {
            $icon = $category->getIcon();
            self::assertNotEmpty($icon);
            self::assertStringStartsWith('tabler:', $icon);
        }
    }
}
