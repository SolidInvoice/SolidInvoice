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

namespace SolidInvoice\QuoteBundle\Enum;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * How long a quote stays valid. Only the resulting date is stored (`Quote::$due`); the period
 * is a way of picking it. None leaves the quote open-ended, Custom lets the user pick the date.
 *
 * @see \SolidInvoice\QuoteBundle\Tests\Enum\QuoteValidityTest
 */
enum QuoteValidity: string
{
    case None = 'none';
    case Days7 = '7_days';
    case Days14 = '14_days';
    case Days30 = '30_days';
    case Days60 = '60_days';
    case Days90 = '90_days';
    case Custom = 'custom';

    public static function forDate(?DateTimeInterface $validUntil): self
    {
        return $validUntil instanceof DateTimeInterface ? self::Custom : self::None;
    }

    /**
     * The last day the quote is valid, counted from `$from`. Null for None and Custom.
     */
    public function validUntil(DateTimeInterface $from): ?CarbonImmutable
    {
        $days = match ($this) {
            self::Days7 => 7,
            self::Days14 => 14,
            self::Days30 => 30,
            self::Days60 => 60,
            self::Days90 => 90,
            self::None, self::Custom => null,
        };

        return null === $days ? null : CarbonImmutable::instance($from)->startOfDay()->addDays($days);
    }

    /**
     * Resolve the stored date: a period wins, Custom keeps what the user picked, None clears it.
     */
    public function resolve(DateTimeInterface $from, ?DateTimeInterface $picked): ?DateTimeInterface
    {
        return match ($this) {
            self::None => null,
            self::Custom => $picked,
            default => $this->validUntil($from),
        };
    }

    public function getLabel(): string
    {
        return 'quote.validity.period.' . $this->value;
    }
}
