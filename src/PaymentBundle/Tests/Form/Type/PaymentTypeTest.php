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

namespace SolidInvoice\PaymentBundle\Tests\Form\Type;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Money\Currency;
use SolidInvoice\CoreBundle\Form\Type\UuidEntityType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\PaymentBundle\Form\Type\PaymentType;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class PaymentTypeTest extends FormTestCase
{
    /**
     * @throws MathException
     */
    public function testSubmit(): void
    {
        $paymentMethod = $this->faker->name;
        $amount = $this->faker->randomNumber();

        $formData = [
            'payment_method' => $paymentMethod,
            'amount' => $amount,
        ];

        $object = [
            'amount' => BigDecimal::of($amount * 100),
        ];

        $this->assertFormData($this->factory->create(PaymentType::class, [], ['currency' => new Currency('USD'), 'preferred_choices' => [], 'user' => null]), $formData, $object);
    }

    protected function getTypes(): array
    {
        $types = parent::getTypes();

        $types[] = new PaymentType($this->registry, new StimulusHelper(null));
        $types[] = new UuidEntityType($this->registry);

        return $types;
    }
}
