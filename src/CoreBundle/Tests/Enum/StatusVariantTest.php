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

namespace SolidInvoice\CoreBundle\Tests\Enum;

use BackedEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\Enum\HasStatusLabel;
use SolidInvoice\CoreBundle\Enum\StatusVariant;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\UserBundle\Enum\InvitationStatus;

/**
 * The mapping is the deliverable of this change, so it is asserted case by case rather
 * than derived. A match arm that someone edits must fail here loudly.
 */
final class StatusVariantTest extends TestCase
{
    #[DataProvider('provideEveryStatus')]
    public function testEveryStatusMapsToItsRuledVariant(HasStatusLabel $status, StatusVariant $expected): void
    {
        self::assertSame($expected, $status->getVariant());
    }

    /**
     * @return iterable<string, array{HasStatusLabel, StatusVariant}>
     */
    public static function provideEveryStatus(): iterable
    {
        // Invoice
        yield 'invoice new' => [InvoiceStatus::New, StatusVariant::Neutral];
        yield 'invoice draft' => [InvoiceStatus::Draft, StatusVariant::Neutral];
        yield 'invoice pending' => [InvoiceStatus::Pending, StatusVariant::Warning];
        yield 'invoice paid' => [InvoiceStatus::Paid, StatusVariant::Success];
        // Active means the same as Pending, so it must carry the same variant.
        yield 'invoice active' => [InvoiceStatus::Active, StatusVariant::Warning];
        yield 'invoice overdue' => [InvoiceStatus::Overdue, StatusVariant::Danger];
        yield 'invoice cancelled' => [InvoiceStatus::Cancelled, StatusVariant::Neutral];
        yield 'invoice archived' => [InvoiceStatus::Archived, StatusVariant::Neutral];

        // Recurring invoice
        yield 'recurring new' => [RecurringInvoiceStatus::New, StatusVariant::Neutral];
        yield 'recurring active' => [RecurringInvoiceStatus::Active, StatusVariant::Info];
        yield 'recurring complete' => [RecurringInvoiceStatus::Complete, StatusVariant::Neutral];
        yield 'recurring draft' => [RecurringInvoiceStatus::Draft, StatusVariant::Neutral];
        yield 'recurring paused' => [RecurringInvoiceStatus::Paused, StatusVariant::Warning];
        yield 'recurring cancelled' => [RecurringInvoiceStatus::Cancelled, StatusVariant::Neutral];
        yield 'recurring archived' => [RecurringInvoiceStatus::Archived, StatusVariant::Neutral];

        // Quote
        yield 'quote new' => [QuoteStatus::New, StatusVariant::Neutral];
        yield 'quote draft' => [QuoteStatus::Draft, StatusVariant::Neutral];
        yield 'quote pending' => [QuoteStatus::Pending, StatusVariant::Warning];
        yield 'quote accepted' => [QuoteStatus::Accepted, StatusVariant::Success];
        yield 'quote cancelled' => [QuoteStatus::Cancelled, StatusVariant::Neutral];
        yield 'quote declined' => [QuoteStatus::Declined, StatusVariant::Danger];
        yield 'quote archived' => [QuoteStatus::Archived, StatusVariant::Neutral];

        // Payment
        yield 'payment unknown' => [PaymentStatus::Unknown, StatusVariant::Warning];
        yield 'payment failed' => [PaymentStatus::Failed, StatusVariant::Danger];
        yield 'payment suspended' => [PaymentStatus::Suspended, StatusVariant::Warning];
        yield 'payment expired' => [PaymentStatus::Expired, StatusVariant::Danger];
        yield 'payment pending' => [PaymentStatus::Pending, StatusVariant::Warning];
        yield 'payment cancelled' => [PaymentStatus::Cancelled, StatusVariant::Neutral];
        yield 'payment new' => [PaymentStatus::New, StatusVariant::Info];
        yield 'payment captured' => [PaymentStatus::Captured, StatusVariant::Success];
        yield 'payment authorized' => [PaymentStatus::Authorized, StatusVariant::Info];
        // A refund leaves the captured total while the invoice stays Paid.
        yield 'payment refunded' => [PaymentStatus::Refunded, StatusVariant::Warning];
        yield 'payment credit' => [PaymentStatus::Credit, StatusVariant::Info];

        // Client
        yield 'client active' => [ClientStatus::Active, StatusVariant::Info];
        yield 'client inactive' => [ClientStatus::Inactive, StatusVariant::Neutral];
        yield 'client archived' => [ClientStatus::Archived, StatusVariant::Neutral];

        // Invitation
        yield 'invitation pending' => [InvitationStatus::Pending, StatusVariant::Warning];
        yield 'invitation expired' => [InvitationStatus::Expired, StatusVariant::Danger];
    }

    /**
     * @param class-string<HasStatusLabel&BackedEnum> $enumClass
     */
    #[DataProvider('provideStatusEnums')]
    public function testEveryCaseOfEveryEnumIsCovered(string $enumClass): void
    {
        $mapped = [];

        foreach (self::provideEveryStatus() as [$status, $_variant]) {
            if ($status instanceof $enumClass) {
                $mapped[] = $status;
            }
        }

        self::assertSame($enumClass::cases(), $mapped);
    }

    /**
     * @return iterable<string, array{class-string<HasStatusLabel&BackedEnum>}>
     */
    public static function provideStatusEnums(): iterable
    {
        yield 'invoice' => [InvoiceStatus::class];
        yield 'recurring invoice' => [RecurringInvoiceStatus::class];
        yield 'quote' => [QuoteStatus::class];
        yield 'payment' => [PaymentStatus::class];
        yield 'client' => [ClientStatus::class];
        yield 'invitation' => [InvitationStatus::class];
    }

    public function testTheVariantValueIsTheChipModifier(): void
    {
        // The backing values are the `.status-chip--{variant}` modifiers in
        // assets/scss/components/_status-chip.scss.
        self::assertSame(
            ['neutral', 'info', 'success', 'warning', 'danger'],
            array_column(StatusVariant::cases(), 'value')
        );
    }
}
