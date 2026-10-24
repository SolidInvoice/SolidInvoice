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

namespace SolidInvoice\EInvoiceBundle\Tests\Channel;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Channel\TransmissionMessage;
use SolidInvoice\EInvoiceBundle\Channel\TransmissionResult;
use SolidInvoice\EInvoiceBundle\Enum\TransmissionState;
use SolidInvoice\EInvoiceBundle\Enum\ViolationSeverity;

final class TransmissionResultTest extends TestCase
{
    public function testDefaults(): void
    {
        $result = new TransmissionResult(TransmissionState::InProgress);

        self::assertSame(TransmissionState::InProgress, $result->state);
        self::assertNull($result->externalId);
        self::assertNull($result->receipt);
        self::assertSame([], $result->messages);
        self::assertNull($result->occurredAt);
        self::assertNull($result->pollAfter);
    }

    public function testMessagesIsAListOfTransmissionMessage(): void
    {
        $message = new TransmissionMessage('BR-01', 'Missing seller VAT identifier', ViolationSeverity::Error);

        $result = new TransmissionResult(
            TransmissionState::Rejected,
            externalId: 'EXT-1',
            receipt: '<UPO/>',
            messages: [$message],
            occurredAt: CarbonImmutable::parse('2026-10-05T00:00:00+00:00'),
            pollAfter: CarbonImmutable::parse('2026-10-05T01:00:00+00:00'),
        );

        self::assertSame(TransmissionState::Rejected, $result->state);
        self::assertSame('EXT-1', $result->externalId);
        self::assertSame('<UPO/>', $result->receipt);
        self::assertSame([$message], $result->messages);
        self::assertEquals(CarbonImmutable::parse('2026-10-05T00:00:00+00:00'), $result->occurredAt);
        self::assertEquals(CarbonImmutable::parse('2026-10-05T01:00:00+00:00'), $result->pollAfter);
    }
}
