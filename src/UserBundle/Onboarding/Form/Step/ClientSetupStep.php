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
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ClientSetupStep extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('clientName', TextType::class, [
            'label' => 'onboarding.client.fields.name.label',
            'required' => false,
            'attr' => [
                'placeholder' => 'onboarding.client.fields.name.placeholder',
            ],
            'help' => 'onboarding.client.fields.name.help',
            'translation_domain' => 'onboarding',
        ]);

        $builder->add('clientEmail', EmailType::class, [
            'label' => 'onboarding.client.fields.email.label',
            'required' => false,
            'attr' => [
                'placeholder' => 'client@example.com',
            ],
            'help' => 'onboarding.client.fields.email.help',
            'translation_domain' => 'onboarding',
        ]);
    }
}
