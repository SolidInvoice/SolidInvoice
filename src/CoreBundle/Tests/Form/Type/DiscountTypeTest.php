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

namespace SolidInvoice\CoreBundle\Tests\Form\Type;

use Money\Currency;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\MoneyBundle\Entity\Money;
use Symfony\Component\Form\PreloadedExtension;

class DiscountTypeTest extends FormTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Money::setBaseCurrency('USD');
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension([new DiscountType(new Currency('USD'))], []),
        ];
    }

    public function testSubmit(): void
    {
        foreach ($this->discountProvider() as $discountItem) {
            $formData = [
                'type' => $discountItem[0],
                'value' => $discountItem[1],
            ];

            $object = new Discount();
            $object->setType($discountItem[0]);
            $object->setValue($discountItem[1]);

            $this->assertFormData(DiscountType::class, $formData, $object);
        }
    }

    public function discountProvider()
    {
        yield [Discount::TYPE_PERCENTAGE, $this->faker->numberBetween(0, 100)];
        yield [Discount::TYPE_MONEY, $this->faker->numberBetween(0, 100)];
    }
}
