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

namespace SolidInvoice\CoreBundle\Tests\PHPStan\data;

use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

function ulid(Ulid $ulid): string
{
    return $ulid->toRfc4122();
}

function uuid(Uuid $uuid): string
{
    return $uuid->toRfc4122();
}

function base58(Ulid $ulid): string
{
    return $ulid->toBase58();
}
