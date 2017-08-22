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

use SolidInvoice\ClientBundle\Form\Type\AddressType as ParentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            public function transform($value)
            {
                if (!is_string($value)) {
                    return null;
                }

                return json_decode($value, true);
            }

            public function reverseTransform($value)
            {
                if (null == $value) {
                    return $value;
                }

                return json_encode($value);
            }
        });
    }

    public function getParent()
    {
        return ParentType::class;
    }

    public function getBlockPrefix()
    {
        return 'settings_address';
    }
}
