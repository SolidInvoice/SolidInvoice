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

namespace SolidInvoice\TaxBundle\Enum;

use Brick\Math\RoundingMode;

enum RoundingStrategy: string
{
    case HalfEven = 'HalfEven';
    case HalfUp = 'HalfUp';
    case HalfDown = 'HalfDown';
    case Up = 'Up';
    case Down = 'Down';

    public function toRoundingMode(): RoundingMode
    {
        return match ($this) {
            self::HalfEven => RoundingMode::HalfEven,
            self::HalfUp => RoundingMode::HalfUp,
            self::HalfDown => RoundingMode::HalfDown,
            self::Up => RoundingMode::Up,
            self::Down => RoundingMode::Down,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::HalfEven => "Half to Even (Banker's Rounding)",
            self::HalfUp => 'Half Up',
            self::HalfDown => 'Half Down',
            self::Up => 'Up (Away from Zero)',
            self::Down => 'Down (Toward Zero)',
        };
    }
}
