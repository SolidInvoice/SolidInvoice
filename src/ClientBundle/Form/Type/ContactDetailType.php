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

namespace SolidInvoice\ClientBundle\Form\Type;

use SolidInvoice\ClientBundle\Entity\AdditionalContactDetail;
use SolidInvoice\ClientBundle\Entity\ContactType as ContactTypeEntity;
use SolidInvoice\CoreBundle\Form\Type\UuidEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\ContactDetailTypeTest
 */
final class ContactDetailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            $builder
                ->create(
                    'type',
                    UuidEntityType::class,
                    [
                        'class' => ContactTypeEntity::class,
                        'placeholder' => 'Choose Type',
                        'constraints' => [
                            new NotBlank(['groups' => 'not_blank_type', 'message' => 'The contact detail type cannot be empty']),
                        ],
                    ]
                )
        );

        $builder->add(
            'value',
            TextType::class,
            [
                'constraints' => [
                    // @TODO: This constraints should not be hard-coded
                    new NotBlank(['groups' => 'not_blank']),
                    new Email(['groups' => 'email']),
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', AdditionalContactDetail::class);

        $resolver->setDefault('validation_groups', function (FormInterface $form) {
            // @codeCoverageIgnoreStart
            $contactDetailsType = $form->get('type')->getData();

            if (! $contactDetailsType instanceof ContactTypeEntity) {
                return ['Default', 'not_blank_type'];
            }

            $type = $contactDetailsType->getName();
            $value = $form->get('value')->getData();

            if (! empty($type) && empty($value)) {
                return ['Default', 'not_blank'];
            }

            if ('email' === strtolower((string) $form->get('type')->getData()->getName())) {
                return ['Default', 'email'];
            }

            return ['Default', 'not_blank'];
            // @codeCoverageIgnoreEnd
        });
    }

    public function getBlockPrefix(): string
    {
        return 'contact_detail';
    }
}
