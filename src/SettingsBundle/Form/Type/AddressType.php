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

use SolidInvoice\ClientBundle\Form\Type\AddressType as ParentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            public function transform(mixed $value): mixed
            {
                if (! is_string($value)) {
                    return null;
                }

                return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }

            public function reverseTransform(mixed $value): mixed
            {
                if (null === $value) {
                    return null;
                }

                return json_encode($value, JSON_THROW_ON_ERROR);
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
