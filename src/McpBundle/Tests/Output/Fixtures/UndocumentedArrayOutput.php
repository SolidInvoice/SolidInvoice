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

namespace SolidInvoice\McpBundle\Tests\Output\Fixtures;

final class UndocumentedArrayOutput
{
    /** @phpstan-ignore missingType.iterableValue (deliberately has no item type: the generator must reject it) */
    public array $values = [];
}
