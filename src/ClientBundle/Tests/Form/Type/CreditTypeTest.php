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

namespace SolidInvoice\ClientBundle\Tests\Form\Type;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use SolidInvoice\ClientBundle\Form\Type\CreditType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;

class CreditTypeTest extends FormTestCase
{
    /**
     * @throws MathException
     */
    public function testSubmit(): void
    {
        $amount = $this->faker->numberBetween(0, 10000);

        $formData = [
            'amount' => $amount,
        ];

        $object = [
            'amount' => BigInteger::of($amount * 100),
        ];

        $this->assertFormData(CreditType::class, $formData, $object);
    }
}
