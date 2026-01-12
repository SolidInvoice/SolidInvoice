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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

final class ClientSetupStep extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('clientName', TextType::class, [
            'label' => 'Client Name',
            'required' => false,
            'attr' => [
                'placeholder' => 'John Doe or Acme Corp',
            ],
        ]);

        $builder->add('clientEmail', EmailType::class, [
            'label' => 'Email Address',
            'required' => false,
            'constraints' => [new Email()],
            'attr' => [
                'placeholder' => 'client@example.com',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['client'],
        ]);
    }
}
