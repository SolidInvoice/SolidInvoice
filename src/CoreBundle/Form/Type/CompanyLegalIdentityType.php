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

namespace SolidInvoice\CoreBundle\Form\Type;

use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Company>
 * @see \SolidInvoice\CoreBundle\Tests\Form\Type\CompanyLegalIdentityTypeTest
 */
final class CompanyLegalIdentityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('legalName', TextType::class, [
                'required' => false,
                'label' => 'company.legal_identity.legal_name',
            ])
            ->add('registrationNumber', TextType::class, [
                'required' => false,
                'label' => 'company.legal_identity.registration_number',
            ])
            ->add('legalForm', TextType::class, [
                'required' => false,
                'label' => 'company.legal_identity.legal_form',
            ])
            ->add('additionalLegalInfo', TextareaType::class, [
                'required' => false,
                'label' => 'company.legal_identity.additional_legal_info',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
