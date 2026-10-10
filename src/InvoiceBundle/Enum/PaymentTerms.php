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

namespace SolidInvoice\InvoiceBundle\Enum;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\SettingsBundle\SystemConfig;

/**
 * When an invoice is due, counted from the invoice date (as QuickBooks and Xero do — "on
 * receipt" means the invoice date, not the day the client opens it). Custom leaves the due
 * date to the user.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Enum\PaymentTermsTest
 */
enum PaymentTerms: string
{
    case DueOnReceipt = 'due_on_receipt';
    case Net7 = 'net_7';
    case Net14 = 'net_14';
    case Net15 = 'net_15';
    case Net30 = 'net_30';
    case Net45 = 'net_45';
    case Net60 = 'net_60';
    case Net90 = 'net_90';
    case EndOfMonth = 'end_of_month';
    case EndOfNextMonth = 'end_of_next_month';
    case Custom = 'custom';

    public const string CONFIG_PATH = 'invoice/payment_terms';

    /**
     * The client's own terms, else the company default.
     */
    public static function forClient(?Client $client, SystemConfig $config): self
    {
        return $client?->getPaymentTerms()
            ?? self::tryFrom((string) $config->get(self::CONFIG_PATH))
            ?? self::Net30;
    }

    public function dueDate(DateTimeInterface $invoiceDate): ?CarbonImmutable
    {
        $date = CarbonImmutable::instance($invoiceDate)->startOfDay();

        return match ($this) {
            self::DueOnReceipt => $date,
            self::Net7 => $date->addDays(7),
            self::Net14 => $date->addDays(14),
            self::Net15 => $date->addDays(15),
            self::Net30 => $date->addDays(30),
            self::Net45 => $date->addDays(45),
            self::Net60 => $date->addDays(60),
            self::Net90 => $date->addDays(90),
            self::EndOfMonth => $date->endOfMonth()->startOfDay(),
            self::EndOfNextMonth => $date->addMonthNoOverflow()->endOfMonth()->startOfDay(),
            self::Custom => null,
        };
    }

    public function getLabel(): string
    {
        return 'payment_terms.' . $this->value;
    }
}
