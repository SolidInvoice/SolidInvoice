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

namespace CSBill\QuoteBundle\Tests\Form\Type;

use CSBill\CoreBundle\Form\Type\DiscountType;
use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\MoneyBundle\Entity\Money;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Form\Type\ItemType;
use CSBill\QuoteBundle\Form\Type\QuoteType;
use CSBill\TaxBundle\Repository\TaxRepository;
use Mockery as M;
use Money\Currency;
use Symfony\Component\Form\PreloadedExtension;

class QuoteTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [
            'client' => null,
            'discount' => 12,
            'items' => [],
            'terms' => '',
            'notes' => '',
            'total' => 0,
            'baseTotal' => 0,
            'tax' => 123,
        ];

        Money::setBaseCurrency('USD');

        $object = new Quote();

        $this->assertFormData($this->factory->create(QuoteType::class, $object), $formData, $object);
    }

    protected function getExtensions()
    {
        $type = new QuoteType(new Currency('USD'));
        $itemType = new ItemType($this->registry);

        $taxRepository = M::mock(TaxRepository::class);
        $taxRepository->shouldReceive('taxRatesConfigured')
            ->andReturn(false);

        $this->registry->shouldReceive('getRepository')
            ->with('CSBillTaxBundle:Tax')
            ->andReturn($taxRepository);

        return [
            new PreloadedExtension([$type, $itemType, new DiscountType(new Currency('USD'))], []),
        ];
    }

    protected function getEntityNamespaces()
    {
        return [
            'CSBillQuoteBundle' => 'CSBill\QuoteBundle\Entity',
            'CSBillTaxBundle' => 'CSBill\TaxBundle\Entity',
            'CSBillClientBundle' => 'CSBill\ClientBundle\Entity',
        ];
    }

    protected function getEntities()
    {
        return [
            'CSBillClientBundle:Client',
            'CSBillQuoteBundle:Quote',
            'CSBillTaxBundle:Tax',
        ];
    }
}
