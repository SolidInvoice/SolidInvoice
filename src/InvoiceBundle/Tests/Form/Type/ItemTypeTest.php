<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\InvoiceBundle\Entity\Item;
use CSBill\InvoiceBundle\Form\Type\ItemType;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\PreloadedExtension;

class ItemTypeTest extends FormTestCase
{
    public function testSubmit()
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

    protected function getEntityNamespaces(): array
    {
        return [
            'CSBillInvoiceBundle' => 'CSBill\InvoiceBundle\Entity',
            'CSBillTaxBundle' => 'CSBill\TaxBundle\Entity',
        ];
    }

    protected function getEntities(): array
    {
        return [
            'CSBillInvoiceBundle:Invoice',
            'CSBillTaxBundle:Tax',
        ];
    }
}
