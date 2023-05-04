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

namespace SolidInvoice\CoreBundle\Exception;

use InvalidArgumentException;

class UnexpectedTypeException extends InvalidArgumentException
{
    public function __construct(mixed $value, string $expectedType)
    {
        parent::__construct(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                $expectedType,
                get_debug_type($value)
            )
        );
    }
}
