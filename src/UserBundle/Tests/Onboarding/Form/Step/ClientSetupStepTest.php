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

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\UserBundle\Onboarding\Form\Step\ClientSetupStep;

final class ClientSetupStepTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'test' => [
                'clientName' => 'John Doe',
                'clientEmail' => 'john@example.com',
            ],
        ];

        $this->assertFormData($this->factory->createNamed('test')->add('test', ClientSetupStep::class), $formData, $formData);
    }

    public function testSubmitWithoutData(): void
    {
        $formData = [
            'test' => [
                'clientName' => '',
                'clientEmail' => '',
            ],
        ];

        $this->assertFormData($this->factory->createNamed('test')->add('test', ClientSetupStep::class), $formData, $formData);
    }
}
