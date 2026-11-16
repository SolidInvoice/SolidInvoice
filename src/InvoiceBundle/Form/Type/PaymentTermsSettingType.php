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

namespace SolidInvoice\InvoiceBundle\Form\Type;

use Override;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The company default on the settings page. Settings are stored as strings, so this maps
 * enum values rather than enum instances (which EnumType would need).
 *
 * @extends AbstractType<string>
 */
final class PaymentTermsSettingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];
        foreach (PaymentTerms::cases() as $term) {
            $choices[$term->getLabel()] = $term->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'empty_data' => PaymentTerms::Net30->value,
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
