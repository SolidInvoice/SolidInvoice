<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Tests\Form\Type;

use CSBill\ClientBundle\Form\Type\CreditType;
use CSBill\CoreBundle\Tests\FormTestCase;
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
