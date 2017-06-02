<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Form\Type;

use CSBill\CoreBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HipChatColorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(
            'choices',
            [
                'Yellow' => 'yellow',
                'Red' => 'red',
                'Gray' => 'gray',
                'Green' => 'green',
                'Purple' => 'purple',
                'Random' => 'random',
            ]
        );

        $resolver->setDefault('placeholder', 'Choose a Color');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2Type::class;
    }
}
