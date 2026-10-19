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

use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Form\Type\CompanyLegalIdentityType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;

final class CompanyLegalIdentityTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $legalName = $this->faker->company();
        $registrationNumber = $this->faker->bothify('####/######/##');
        $legalForm = $this->faker->word();
        $additionalLegalInfo = $this->faker->sentence();

        $formData = [
            'legalName' => $legalName,
            'registrationNumber' => $registrationNumber,
            'legalForm' => $legalForm,
            'additionalLegalInfo' => $additionalLegalInfo,
        ];

        $object = new Company();
        $object->setLegalName($legalName);
        $object->setRegistrationNumber($registrationNumber);
        $object->setLegalForm($legalForm);
        $object->setAdditionalLegalInfo($additionalLegalInfo);

        $this->assertFormData(CompanyLegalIdentityType::class, $formData, $object);
    }

    public function testEveryFieldIsOptional(): void
    {
        $form = $this->factory->create(CompanyLegalIdentityType::class, new Company());

        $form->submit([
            'legalName' => '',
            'registrationNumber' => '',
            'legalForm' => '',
            'additionalLegalInfo' => '',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertCount(0, $form->getErrors(true));
    }

    public function testDoesNotExposeTheCompanyName(): void
    {
        $form = $this->factory->create(CompanyLegalIdentityType::class, new Company());

        self::assertFalse($form->has('name'));
    }
}
