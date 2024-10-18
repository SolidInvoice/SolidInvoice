<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Tests\Form\Type;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Money\Currency;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Form\Type\ItemType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

final class ItemTypeTest extends FormTestCase
{
    /**
     * @throws MathException
     */
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

        $currency = new Currency('USD');

        $object = new Line();
        $object->setDescription($description);
        $object->setQty($qty);
        $object->setPrice(BigDecimal::of($price)->multipliedBy(100));

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => $currency]), $formData, $object);
    }

    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$itemType], []),
        ];
    }
}
