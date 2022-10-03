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

namespace SolidInvoice\CoreBundle\Test\Traits;

use Faker\Factory;
use Faker\Generator;

trait FakerTestTrait
{
    public function getFaker(string $locale = Factory::DEFAULT_LOCALE): Generator
    {
        static $faker;

        if (! $faker) {
            $faker = Factory::create($locale);
        }

        return $faker;
    }
}
