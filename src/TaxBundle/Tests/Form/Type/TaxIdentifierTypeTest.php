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

namespace SolidInvoice\TaxBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Enum\TaxIdentifierScheme;
use SolidInvoice\TaxBundle\Form\Type\TaxIdentifierType;

final class TaxIdentifierTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $label = $this->faker->word();
        $value = $this->faker->bothify('??#########');

        $formData = [
            'label' => $label,
            'value' => $value,
            'scheme' => TaxIdentifierScheme::Vat->value,
            'primary' => true,
        ];

        $object = new TaxIdentifier();
        $object->setLabel($label);
        $object->setValue($value);
        $object->setScheme(TaxIdentifierScheme::Vat);
        $object->setPrimary(true);

        $this->assertFormData(TaxIdentifierType::class, $formData, $object);
    }

    public function testSchemeIsNotRequiredAndDoesNotDefaultToVat(): void
    {
        $label = $this->faker->word();
        $value = $this->faker->bothify('??#########');

        $formData = [
            'label' => $label,
            'value' => $value,
            'primary' => false,
        ];

        $object = new TaxIdentifier();
        $object->setLabel($label);
        $object->setValue($value);
        $object->setPrimary(false);

        $this->assertFormData(TaxIdentifierType::class, $formData, $object);
        self::assertNull($object->getScheme());
    }
}
