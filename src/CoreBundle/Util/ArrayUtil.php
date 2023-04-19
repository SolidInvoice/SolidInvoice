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

namespace SolidInvoice\CoreBundle\Util;

use ArrayAccess;
use Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Traversable;

class ArrayUtil
{
    /**
     * Returns a specific column from an array.
     *
     * @param array|\Traversable $array
     * @param string             $column
     *
     * @throws Exception
     */
    public static function column($array, $column): array
    {
        if (! is_array($array) && ! $array instanceof Traversable) {
            throw new Exception(sprintf('Array or instance of Traversable expected, "%s" given', gettype($array)));
        }

        if (is_array($array[array_key_first($array)])) {
            return array_column($array, $column);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $return = [];

        foreach ($array as $item) {
            if ((is_array($item) || $item instanceof ArrayAccess) && '[' !== $column[0]) {
                $column = '[' . $column . ']';
            }

            $return[] = $accessor->getValue($item, $column);
        }

        return array_filter($return, fn ($item): bool => null !== $item);
    }
}
