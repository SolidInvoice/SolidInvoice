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

namespace SolidInvoice\ClientBundle\Form;

class ConstraintBuilder
{
    public const CONSTRAINT_NAMESPACE = 'Symfony\\Component\\Validator\\Constraints\\';

    /**
     * @static
     */
    public static function build(array $options): array
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
     * @static
     */
    private static function humanize(string $text): string
    {
        return ucwords(str_replace('_', ' ', $text));
    }
}
