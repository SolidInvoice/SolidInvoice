<?php

/*
 * This file is part of the CSBillCoreBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

        $return = array_map(function($item) use ($column, $accessor) {

            if (is_array($item) || $item instanceof \ArrayAccess) {
                $column = '['.$column.']';
            }

            return $accessor->getValue($item, $column);
        }, $array);

        return array_filter($return, function($item) {
            return $item !== null;
        });
    }
}

