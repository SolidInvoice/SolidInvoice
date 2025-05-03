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

use SolidInvoice\InstallBundle\Doctrine\Drivers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Form\Type\DatabaseConfigTypeTest
 */
class DatabaseConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add(
            'driver',
            ChoiceType::class,
            [
                'label' => 'Database Type',
                'choices' => Drivers::getChoiceList(),
                'placeholder' => 'Select Database Driver',
                'constraints' => new NotBlank(),
            ]
        );

        $fields = [
            'host' => [
                null,
                [
                    'constraints' => new NotBlank(),
                ],
            ],
            'port' => [
                IntegerType::class,
                [
                    'constraints' => new Type(['type' => 'integer']),
                    'required' => false,
                ],
            ],
            'user' => [
                null,
                [
                    'constraints' => new NotBlank(),
                ],
            ],
            'password' => [
                PasswordType::class,
                [
                    'required' => false,
                    'always_empty' => false,
                ],
            ],
            'name' => [
                null,
                [
                    'label' => 'Database Name',
                    'constraints' => new NotBlank(),
                    'required' => true,
                ],
            ],
        ];

        foreach ($fields as $name => [$type, $fieldOptions]) {
            $builder->addDependent($name, ['driver'], function (DependentField $field, ?string $driver = null) use ($type, $fieldOptions): void {
                if ($driver === null || $driver === 'sqlite') {
                    return;
                }

                $field->add($type, $fieldOptions);
            });
        }
    }

    public function getBlockPrefix(): string
    {
        return 'database_config';
    }
}
