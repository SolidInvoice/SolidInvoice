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
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Form\Type\ItemType;
use CSBill\MoneyBundle\Entity\Money;
use CSBill\TaxBundle\Repository\TaxRepository;
use Mockery as M;
use Money\Currency;
use Symfony\Component\Form\PreloadedExtension;

class InvoiceTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $notes = $this->faker->text;
        $terms = $this->faker->text;
        $discount = $this->faker->numberBetween(0, 100);
        $formData = [
            'client' => null,
            'discount' => $discount,
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
        $object->setDiscount((float) $discount / 100);
        $object->setTotal(new \Money\Money(0, new Currency('USD')));
        $object->setTax(new \Money\Money(0, new Currency('USD')));
        $object->setBaseTotal(new \Money\Money(0, new Currency('USD')));

        $this->assertFormData($this->factory->create(InvoiceType::class, $data), $formData, $object);
    }

    protected function getExtensions()
    {
        $repository = M::mock(TaxRepository::class);
        $repository->shouldReceive('taxRatesConfigured')
            ->zeroOrMoreTimes()
            ->withNoArgs()
            ->andReturn(false);

        $this->registry->shouldReceive('getRepository')
            ->with('CSBillTaxBundle:Tax')
            ->andReturn($repository);

        $currency = new Currency('USD');

        $invoiceType = new InvoiceType($currency);
        $itemType = new ItemType($this->registry);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$invoiceType, $itemType], []),
        ];
    }

    protected function getEntityNamespaces()
    {
        return [
            'CSBillTaxBundle' => 'CSBill\TaxBundle\Entity',
            'CSBillInvoiceBundle' => 'CSBill\InvoiceBundle\Entity',
            'CSBillClientBundle' => 'CSBill\ClientBundle\Entity',
        ];
    }

    protected function getEntities()
    {
        return [
            'CSBillClientBundle:Client',
            'CSBillInvoiceBundle:Invoice',
            'CSBillTaxBundle:Tax',
        ];
    }
}
