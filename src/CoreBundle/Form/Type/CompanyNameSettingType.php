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

use Override;
use SolidInvoice\CoreBundle\Validator\Constraints\CompanyName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The company name on the settings page. Settings store their form type, not their
 * constraints, so the constraint has to come from the type itself.
 *
 * @extends AbstractType<string>
 */
final class CompanyNameSettingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [new CompanyName()],
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return TextType::class;
    }
}
