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

namespace SolidInvoice\PaymentBundle\Tests\Listener;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Listener\PaymentCompleteListener;

#[CoversClass(PaymentCompleteListener::class)]
final class PaymentCompleteListenerTest extends TestCase
{
    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('statusProvider')]
    public function testAddFlashMessageMatchesExpectedSeverity(PaymentStatus $status, array $expected): void
    {
        self::assertSame($expected, iterator_to_array(PaymentCompleteListener::addFlashMessage($status->value)));
    }

    /**
     * @return Generator<string, array{PaymentStatus, array<string, string>}>
     */
    public static function statusProvider(): Generator
    {
        yield 'captured' => [PaymentStatus::Captured, [FlashResponse::FLASH_SUCCESS => 'payment.flash.status.success']];
        yield 'cancelled' => [PaymentStatus::Cancelled, [FlashResponse::FLASH_INFO => 'payment.flash.status.cancelled']];
        yield 'credit' => [PaymentStatus::Credit, [FlashResponse::FLASH_INFO => 'payment.flash.status.credit']];
        yield 'pending' => [PaymentStatus::Pending, [FlashResponse::FLASH_WARNING => 'payment.flash.status.pending']];
        yield 'expired' => [PaymentStatus::Expired, [FlashResponse::FLASH_DANGER => 'payment.flash.status.expired']];
        yield 'failed' => [PaymentStatus::Failed, [FlashResponse::FLASH_DANGER => 'payment.flash.status.failed']];
        yield 'new' => [PaymentStatus::New, [FlashResponse::FLASH_INFO => 'payment.flash.status.new']];
        yield 'suspended' => [PaymentStatus::Suspended, [FlashResponse::FLASH_WARNING => 'payment.flash.status.suspended']];
        yield 'authorized' => [PaymentStatus::Authorized, [FlashResponse::FLASH_INFO => 'payment.flash.status.authorized']];
        yield 'refunded' => [PaymentStatus::Refunded, [FlashResponse::FLASH_WARNING => 'payment.flash.status.refunded']];
        yield 'unknown' => [PaymentStatus::Unknown, [FlashResponse::FLASH_WARNING => 'payment.flash.status.unconfirmed']];
    }

    public function testAddFlashMessageFallsBackToDangerForNonStatusValue(): void
    {
        self::assertSame(
            [FlashResponse::FLASH_DANGER => 'payment.flash.status.unknown'],
            iterator_to_array(PaymentCompleteListener::addFlashMessage('not-a-status')),
        );
    }

    public function testAddFlashMessageFallsBackToDangerForEmptyStringStatus(): void
    {
        self::assertSame(
            [FlashResponse::FLASH_DANGER => 'payment.flash.status.unknown'],
            iterator_to_array(PaymentCompleteListener::addFlashMessage('')),
        );
    }

    public function testNoPaymentStatusCaseFallsThroughToTheDefaultArm(): void
    {
        foreach (PaymentStatus::cases() as $case) {
            $flash = iterator_to_array(PaymentCompleteListener::addFlashMessage($case->value));

            self::assertNotContains('payment.flash.status.unknown', $flash, sprintf('PaymentStatus::%s has no explicit arm and falls through to default.', $case->name));
        }
    }
}
