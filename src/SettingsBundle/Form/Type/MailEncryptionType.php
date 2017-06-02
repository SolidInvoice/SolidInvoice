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

namespace CSBill\SettingsBundle\Form\Type;

use CSBill\CoreBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailEncryptionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(
            'choices',
            [
                'SSL' => 'ssl',
                'TLS' => 'tls',
            ]
        );

        $resolver->setDefault('placeholder', 'None');
    }

    public function getParent()
    {
        return Select2Type::class;
    }
}