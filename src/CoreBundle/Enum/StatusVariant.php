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

namespace SolidInvoice\CoreBundle\Enum;

/**
 * The semantic variants of the status chip (DESIGN.md section 5).
 *
 * The backing value is the `.status-chip--{variant}` modifier in
 * `assets/scss/components/_status-chip.scss`. Every variant is an AA-safe
 * text colour on its own tint, so a status can never render below 4.5:1.
 */
enum StatusVariant: string
{
    case Neutral = 'neutral';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}
