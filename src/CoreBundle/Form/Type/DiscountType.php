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

namespace SolidInvoice\CoreBundle\Form\Type;

use SolidInvoice\CoreBundle\Entity\Discount;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /**
     * @var Currency
     */
    private $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['types'] = self::DISCOUNT_TYPES;
        $view->vars['currency'] = $options['currency'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', HiddenType::class, ['empty_data' => 'percentage', 'placeholder' => 'percentage', 'attr' => ['class' => 'discount-type']]);
        $builder->add(
            'value',
            TextType::class,
            [
                'attr' => [
                    'class' => 'discount-value',
                ],
            ]
        );

        $builder->get('value')->addViewTransformer(new class() implements DataTransformerInterface {
            public function transform($discount)
            {
                if (!$discount instanceof Money) {
                    return $discount;
                }

                /* @var Money $discount */
                return ((int) $discount->getAmount()) / 100;
            }

            public function reverseTransform($value)
            {
                return $value;
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Discount::class);
        $resolver->setDefault('currency', $this->currency->getCode());
        $resolver->setAllowedTypes('currency', 'string');
    }

    public function getBlockPrefix()
    {
        return 'discount';
    }
}
