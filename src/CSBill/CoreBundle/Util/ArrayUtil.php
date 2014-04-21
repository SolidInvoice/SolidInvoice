<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ArrayUtil
{
    /**
     * Returns a specific column from an array
     *
     * @param  array      $array
     * @param  string     $column
     * @throws \Exception
     * @return array
     */
    public static function column(array $array, $column)
    {
        if (empty($array)) {
            throw new \Exception("Array cannot be empty");
        }

        reset($array);

        // Forward-compatible with PHP 5.5
        if (function_exists('array_column') && is_array($array[key($array)])) {
            return array_column($array, $column);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $return = array_map(function ($item) use ($column, $accessor) {

            if (is_array($item) || $item instanceof \ArrayAccess) {
                $column = '['.$column.']';
            }

            return $accessor->getValue($item, $column);
        }, $array);

        return array_filter($return, function ($item) {
            return $item !== null;
        });
    }
}
