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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

final class MailgunApiTransportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'domain',
            null,
            [
                'constraints' => new Constraints\NotBlank(['groups' => 'mailgun']),
            ]
        );

        $builder->add(
            'key',
            null,
            [
                'constraints' => new Constraints\NotBlank(['groups' => 'mailgun']),
            ]
        );
    }
}
