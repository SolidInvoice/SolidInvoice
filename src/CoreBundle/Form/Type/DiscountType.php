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

use Money\Currency;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Transformer\DiscountTransformer;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Form\Type\DiscountTypeTest
 */
class DiscountType extends AbstractType
{
    private const DISCOUNT_TYPES = [
        'percentage' => [
            'symbol' => '%',
            'name' => 'percentage',
        ],
        'money' => [
            'symbol' => '',
            'name' => 'money',
        ],
    ];

    public function __construct(
        private readonly SystemConfig $systemConfig
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['types'] = self::DISCOUNT_TYPES;
        $view->vars['currency'] = $options['currency']->getCode();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'type',
            ChoiceType::class,
            [
                /*'empty_data' => 'percentage',
                'placeholder' => 'percentage',*/
                'attr' => [
                    'class' => 'discount-type'
                ],
                'choices' => [
                    '%' => 'percentage',
                    $options['currency']->getCode() => 'money',
                ],
            ]
        );

        $builder->add(
            'value',
            TextType::class,
            [
                'attr' => [
                    'class' => 'discount-value',
                ],
            ]
        );

        $builder->get('value')->addViewTransformer(new DiscountTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Discount::class);
        $resolver->setDefault('currency', $this->systemConfig->getCurrency());
        $resolver->setAllowedTypes('currency', [Currency::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'discount';
    }
}
