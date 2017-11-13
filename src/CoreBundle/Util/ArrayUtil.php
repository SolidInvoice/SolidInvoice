<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ArrayUtil
{
    /**
     * Returns a specific column from an array.
     *
     * @param array|\Traversable $array
     * @param string             $column
     *
     * @throws \Exception
     *
     * @return array
     */
    public static function column($array, $column): array
    {
        if (!is_array($array) && !$array instanceof \Traversable) {
            throw new \Exception(sprintf('Array or instance of Traversable expected, "%s" given', gettype($array)));
        }

        reset($array);

        if (is_array($array[key($array)])) {
            return array_column($array, $column);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $return = [];

        foreach ($array as $item) {
            if (is_array($item) || $item instanceof \ArrayAccess) {
                if ('[' !== $column[0]) {
                    $column = '['.$column.']';
                }
            }

            $return[] = $accessor->getValue($item, $column);
        }

        return array_filter($return, function ($item): bool {
            return null !== $item;
        });
    }
}
