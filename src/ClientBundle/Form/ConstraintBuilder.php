<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form;

class ConstraintBuilder
{
    const CONSTRAINT_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';

    /**
     * @param array $options
     *
     * @return array
     * @static
     */
    public static function build(array $options)
    {
        $constraints = [];

        foreach ($options as $constraint) {
            $constraint = str_replace(' ', '', self::humanize($constraint));
            if (class_exists($class = (self::CONSTRAINT_NAMESPACE.$constraint))) {
                $constraints[] = new $class();
            }
        }

        return $constraints;
    }

    /**
     * @param string $text
     *
     * @return string
     * @static
     */
    private static function humanize($text)
    {
        return ucwords(str_replace('_', ' ', $text));
    }
}
