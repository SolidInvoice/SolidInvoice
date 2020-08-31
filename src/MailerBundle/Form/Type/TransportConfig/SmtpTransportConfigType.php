<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Form\Type\TransportConfig;

use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

final class SmtpTransportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'host',
            null,
            [
                'constraints' => new Constraints\NotBlank(['groups' => 'smtp']),
            ]
        );

        $builder->add(
            'port',
            IntegerType::class,
            [
                'constraints' => new Constraints\Type(['groups' => ['smtp'], 'type' => 'integer']),
                'required' => false,
            ]
        );

        $builder->add(
            'encryption',
            Select2Type::class,
            [
                'placeholder' => 'None',
                'choices' => [
                    'SSL' => 'ssl',
                    'TLS' => 'tls',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'user',
            null,
            [
                'constraints' => new Constraints\NotBlank(['groups' => 'gmail']),
                'required' => false,
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'constraints' => new Constraints\NotBlank(['groups' => 'gmail']),
                'required' => false,
            ]
        );
    }
}
