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

namespace SolidInvoice\TaxBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Form\Type\TaxType;

final class TaxTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $name = $this->faker->name;
        $rate = $this->faker->randomFloat(2, 0, 100);
        $type = ucwords((string) $this->faker->randomKey(Tax::getTypes()));
        $category = $this->faker->randomElement(TaxCategory::cases());
        $compound = $this->faker->boolean();

        $formData = [
            'name' => $name,
            'rate' => $rate,
            'type' => $type,
            'category' => $category->value,
            'compound' => $compound,
        ];

        $object = new Tax();
        $object->setName($name);
        $object->setRate($rate);
        $object->setType($type);
        $object->setCategory($category);
        $object->setCompound($compound);

        $this->assertFormData(TaxType::class, $formData, $object);
    }
}
