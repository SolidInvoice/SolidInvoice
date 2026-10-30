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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Listener\PaymentReceivedListener;
use SolidInvoice\PaymentBundle\Notification\PaymentReceivedNotification;

#[CoversClass(PaymentReceivedListener::class)]
final class PaymentReceivedListenerTest extends TestCase
{
    public function testACapturedPaymentNotifies(): void
    {
        $notificationManager = $this->createMock(NotificationManager::class);
        $notificationManager->expects(self::once())
            ->method('sendNotification')
            ->with(self::isInstanceOf(PaymentReceivedNotification::class));

        new PaymentReceivedListener($notificationManager)
            ->onPaymentCapture(new PaymentCompleteEvent(new Payment()->setStatus(PaymentStatus::Captured)));
    }

    #[DataProvider('notReceivedStatuses')]
    public function testAPaymentThatWasNotReceivedDoesNotNotify(PaymentStatus $status): void
    {
        $notificationManager = $this->createMock(NotificationManager::class);
        $notificationManager->expects(self::never())->method('sendNotification');

        new PaymentReceivedListener($notificationManager)
            ->onPaymentCapture(new PaymentCompleteEvent(new Payment()->setStatus($status)));
    }

    /**
     * @return iterable<string, array{PaymentStatus}>
     */
    public static function notReceivedStatuses(): iterable
    {
        foreach (PaymentStatus::cases() as $status) {
            if ($status !== PaymentStatus::Captured) {
                yield $status->value => [$status];
            }
        }
    }
}
