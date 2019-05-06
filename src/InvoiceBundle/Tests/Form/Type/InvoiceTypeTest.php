<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Form\Type\ItemType;
use SolidInvoice\MoneyBundle\Entity\Money;
use Money\Currency;
use Symfony\Component\Form\PreloadedExtension;

class InvoiceTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $notes = $this->faker->text;
        $terms = $this->faker->text;
        $discountValue = $this->faker->numberBetween(0, 100);
        $formData = [
            'client' => null,
            'discount' => [
                'value' => $discountValue,
                'type' => Discount::TYPE_PERCENTAGE,
            ],
            'recurring' => false,
            'recurringInfo' => null,
            'items' => [],
            'notes' => $notes,
            'terms' => $terms,
            'total' => 0,
            'baseTotal' => 0,
            'tax' => 0,
        ];

        Money::setBaseCurrency('USD');

        $object = new Invoice();
        $data = clone $object;
        $data->setUuid($object->getUuid());

        $object->setTerms($terms);
        $object->setNotes($notes);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue($discountValue);
        $object->setDiscount($discount);
        $object->setTotal(new \Money\Money(0, new Currency('USD')));
        $object->setTax(new \Money\Money(0, new Currency('USD')));
        $object->setBaseTotal(new \Money\Money(0, new Currency('USD')));

        $this->assertFormData($this->factory->create(InvoiceType::class, $data), $formData, $object);
    }

    protected function getExtensions()
    {
        $currency = new Currency('USD');

        $invoiceType = new InvoiceType($currency);
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$invoiceType, $itemType, new DiscountType($currency)], []),
        ];
    }
}
