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

namespace SolidInvoice\ClientBundle\Tests\Form\Type;

use SolidInvoice\ClientBundle\Form\Type\CreditType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use Money\Currency;
use Money\Money;

class CreditTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $amount = $this->faker->numberBetween(0, 10000);

        $formData = [
            'amount' => $amount,
        ];

        $object = [
            'amount' => new Money($amount * 100, new Currency('USD')),
        ];

        $this->assertFormData(CreditType::class, $formData, $object);
    }
}
