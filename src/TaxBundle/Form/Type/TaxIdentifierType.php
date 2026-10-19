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

namespace SolidInvoice\TaxBundle\Form\Type;

use Override;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Enum\TaxIdentifierScheme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<TaxIdentifier>
 * @see \SolidInvoice\TaxBundle\Tests\Form\Type\TaxIdentifierTypeTest
 */
final class TaxIdentifierType extends AbstractType
{
    /**
     * Suggestions offered through the `label` field's datalist. Display-only: the field is
     * free text, so this is not a closed choice list and not an AGENTS.md §3 enum candidate.
     *
     * @var list<string>
     */
    public const array LABEL_PRESETS = ['VAT', 'GSTIN', 'TIN', 'ABN', 'CNPJ', 'TRN', 'Other'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
            'attr' => [
                'list' => 'tax-identifier-label-presets',
                'maxlength' => 32,
            ],
        ]);

        $builder->add('value', TextType::class, [
            'required' => true,
        ]);

        $builder->add('scheme', EnumType::class, [
            'class' => TaxIdentifierScheme::class,
            'required' => false,
            'placeholder' => 'form.placeholder.choose',
            'choice_label' => static fn (TaxIdentifierScheme $scheme): string => $scheme->translationKey(),
        ]);

        $builder->add('primary', CheckboxType::class, [
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaxIdentifier::class,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'tax_identifier';
    }
}
