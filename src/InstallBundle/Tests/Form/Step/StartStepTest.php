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

namespace SolidInvoice\InstallBundle\Tests\Form\Step;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InstallBundle\Form\Step\StartStep;

/**
 * @covers \SolidInvoice\InstallBundle\Form\Step\StartStep
 */
final class StartStepTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $form = $this->factory->create(StartStep::class);

        // StartStep is an empty form, so it should just work with empty data
        $form->submit([]);

        self::assertTrue($form->isSynchronized());
        // Empty forms return an empty array, not null
        self::assertIsArray($form->getData());
    }

    public function testFormView(): void
    {
        $form = $this->factory->create(StartStep::class);
        $view = $form->createView();

        self::assertEmpty($view->children);
    }
}
