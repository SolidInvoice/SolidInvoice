<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailFormatType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(
            'choices',
            [
                'Both' => 'both',
                'HTML' => 'html',
                'Text' => 'text',
            ]
        );

        $resolver->setDefault('expanded', true);
        $resolver->setDefault('multiple', false);
        $resolver->setDefault('placeholder', false);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
