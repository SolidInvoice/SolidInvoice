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

use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType as TaxTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\TaxBundle\Tests\Form\Type\TaxTypeTest
 * @extends AbstractType<Tax>
 */
class TaxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, ['sanitize_html' => true, 'allow_single_quotes' => true]);
        $builder->add('rate', PercentType::class, ['scale' => 2, 'type' => 'integer']);

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'choices' => array_column(array_map(static fn (TaxTypeEnum $type): array => [$type->getLabel(), $type->value], TaxTypeEnum::cases()), 1, 0),
                'help' => 'tax.rates.explanation',
                'help_html' => true,
                'placeholder' => 'tax.rates.type.select',
            ]
        );

        $builder->add(
            'category',
            EnumType::class,
            [
                'class' => TaxCategory::class,
                'choice_label' => static fn (TaxCategory $c) => $c->getLabel(),
                'placeholder' => false,
            ]
        );

        $builder->add(
            'compound',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        $builder->add(
            'default',
            CheckboxType::class,
            [
                'label' => 'tax.rates.is_default.label',
                'help' => 'tax.rates.is_default.help',
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Tax::class]);
    }
}
