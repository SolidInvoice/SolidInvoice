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

namespace SolidInvoice\InvoiceBundle\Tests\Form\Type;

use Money\Currency;
use Money\Money;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use Symfony\Component\Form\PreloadedExtension;

class ItemTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $description = $this->faker->text;
        $price = $this->faker->randomNumber(3);
        $qty = $this->faker->randomFloat(2);

        $formData = [
            'description' => $description,
            'price' => $price,
            'qty' => $qty,
        ];

        $object = new Item();
        $object->setDescription($description);
        $object->setQty($qty);
        $object->setPrice(new Money($price * 100, new Currency('USD')));

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => 'USD']), $formData, $object);
    }

    protected function getExtensions()
    {
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$itemType], []),
        ];
    }
}
