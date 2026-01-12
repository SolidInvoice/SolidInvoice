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

namespace SolidInvoice\UserBundle\Tests\Onboarding\Form\Step;

use Brick\Math\BigDecimal;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\UserBundle\Onboarding\Form\Step\InvoiceSetupStep;

final class InvoiceSetupStepTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'test' => [
                'invoiceDescription' => 'Website Design',
                'invoiceAmount' => '1500.00',
            ],
        ];

        $expectedData = [
            'test' => [
                'invoiceDescription' => 'Website Design',
                'invoiceAmount' => BigDecimal::of('150000'),
            ],
        ];

        $form = $this->factory->createNamed('test')->add('test', InvoiceSetupStep::class, options: ['currency' => 'USD']);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function testSubmitWithoutData(): void
    {
        $formData = [
            'test' => [
                'invoiceDescription' => '',
                'invoiceAmount' => '',
            ],
        ];

        $expectedData = [
            'test' => [
                'invoiceDescription' => null,
                'invoiceAmount' => BigDecimal::of('0'),
            ],
        ];

        $form = $this->factory->createNamed('test')->add('test', InvoiceSetupStep::class, options: ['currency' => 'USD']);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }
}
