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

use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class CompanySetupStep extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('companyName', TextType::class, [
            'label' => 'onboarding.company.fields.name.label',
            'required' => true,
            'attr' => [
                'placeholder' => 'onboarding.company.fields.name.placeholder',
                'autofocus' => true,
            ],
            'help' => 'onboarding.company.fields.name.help',
            'translation_domain' => 'onboarding',
        ]);

        $builder->add('companyCurrency', CurrencyType::class, [
            'label' => 'onboarding.company.fields.currency.label',
            'required' => true,
            'help' => 'onboarding.company.fields.currency.help',
            'translation_domain' => 'onboarding',
        ]);
    }
}
