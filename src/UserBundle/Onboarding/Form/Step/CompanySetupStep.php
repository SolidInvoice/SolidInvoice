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

namespace SolidInvoice\UserBundle\Onboarding\Form\Step;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CompanySetupStep extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('companyName', TextType::class, [
            'label' => 'Company Name',
            'required' => true,
            'constraints' => [new NotBlank()],
            'attr' => [
                'placeholder' => 'Your Company Name',
                'autofocus' => true,
            ],
        ]);

        $builder->add('companyCurrency', CurrencyType::class, [
            'label' => 'Currency',
            'required' => true,
            'help' => 'This will be used for all your invoices',
        ]);
    }
}
