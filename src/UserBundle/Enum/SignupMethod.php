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

namespace SolidInvoice\UserBundle\Enum;

/**
 * How a user account was created. Null on users that predate this column.
 */
enum SignupMethod: string
{
    case Registration = 'registration';
    case Invitation = 'invitation';
    case Google = 'google';
    case OneTap = 'one_tap';
}
