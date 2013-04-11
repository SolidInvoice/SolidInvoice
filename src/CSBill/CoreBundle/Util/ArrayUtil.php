<?php

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
        if (!is_array($array) || empty($array)) {
            throw new \Exception("Array cannot be empty");
        }

        // Forward-compatible with PHP 5.5
        if (function_exists('array_column')) {
            return array_column($array, $column);
        }

        $accessor = PropertyAccess::getPropertyAccessor();

        $return = array_map(function($item) use ($column, $accessor) {

            if(is_array($item) || $item instanceof \ArrayAccess) {
                $column = '['.$column.']';
            }

            return $accessor->getValue($item, $column);
        }, $array);

        return array_filter($return, function($item) {
            return $item !== null;
        });
    }
}
