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

namespace SolidInvoice\MailerBundle\Form\Type\TransportConfig;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

/**
 * @see \SolidInvoice\MailerBundle\Tests\Form\Type\TransportConfig\SesTransportConfigTypeTest
 */
final class SesTransportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'accessKey',
            null,
            [
                'constraints' => new NotBlank(['groups' => 'amazon_ses']),
            ]
        );

        $builder->add(
            'accessSecret',
            PasswordType::class,
            [
                'constraints' => new NotBlank(['groups' => ['amazon_ses']]),
            ]
        );

        $builder->add(
            'region',
            null,
            [
                'attr' => [
                    'placeholder' => 'eu-west-1',
                ],
            ]
        );
    }
}
