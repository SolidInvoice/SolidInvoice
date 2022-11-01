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

namespace SolidInvoice\SettingsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThemeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'choices',
            [
                'Default' => 'skin-solidinvoice-default',
                'Blue' => 'skin-blue',
                'Blue Light' => 'skin-blue-light',
                'Yellow' => 'skin-yellow',
                'Yellow Light' => 'skin-yellow-light',
                'Green' => 'skin-green',
                'Green Light' => 'skin-green-light',
                'Purple' => 'skin-purple',
                'Purple Light' => 'skin-purple-light',
                'Red' => 'skin-red',
                'Red Light' => 'skin-red-light',
                'Black' => 'skin-black',
                'Black Light' => 'skin-black-light',
            ]
        );

        $resolver->setDefault('placeholder', 'Choose Site Theme');
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
