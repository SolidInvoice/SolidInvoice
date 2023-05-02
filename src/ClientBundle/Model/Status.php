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

namespace SolidInvoice\ClientBundle\Model;

class Status
{
    final public const STATUS_ACTIVE = 'active';

    final public const STATUS_INACTIVE = 'inactive';

    final public const STATUS_ARCHIVED = 'archived';
}
