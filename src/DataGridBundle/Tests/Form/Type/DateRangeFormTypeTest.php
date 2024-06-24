<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\DataGridBundle\Form\Type\DateRangeFormType;

/**
 * @covers \SolidInvoice\DataGridBundle\Form\Type\DateRangeFormType
 */
final class DateRangeFormTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'start' => '2020-01-01',
            'end' => '2020-01-31',
        ];

        $form = $this->factory->create(DateRangeFormType::class);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSubmitted());
        self::assertSame($formData, $form->getData());
    }
}
