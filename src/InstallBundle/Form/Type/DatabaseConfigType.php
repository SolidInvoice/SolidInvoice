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

namespace SolidInvoice\InstallBundle\Form\Type;

use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class DatabaseConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $drivers = $options['drivers'];

        $builder->add(
            'driver',
            Select2Type::class,
            [
                'choices' => array_flip($drivers),
                'placeholder' => 'Select Database Driver',
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'host',
            null,
            [
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'port',
            IntegerType::class,
            [
                'constraints' => new Type(['type' => 'integer']),
                'required' => false,
            ]
        );

        $builder->add(
            'user',
            null,
            [
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'name',
            null,
            [
                'label' => 'Database Name',
                'constraints' => new NotBlank(),
                'required' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['drivers']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'database_config';
    }
}
