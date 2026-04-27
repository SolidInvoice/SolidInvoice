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

namespace SolidInvoice\SaasBundle\Form\Type;

use SolidInvoice\CoreBundle\Validator\Constraints\NotApplicationUrlHost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Hostname;

final class CustomDomainType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Hostname(['requireTld' => true]),
                new NotApplicationUrlHost(),
            ],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
