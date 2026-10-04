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
use Override;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Form\Transformer\DiscountTransformer;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Form\Type\DiscountTypeTest
 * @extends AbstractType<Discount>
 */
class DiscountType extends AbstractType
{
    private const array DISCOUNT_TYPES = [
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

    /**
     * @param FormInterface<mixed> $form
     */
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
                'attr' => [
                    'class' => 'discount-type'
                ],
                'choices' => [
                    '%' => 'percentage',
                    $options['currency']->getCode() => 'money',
                ],
            ]
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                $this->addValueField($event->getForm(), $event->getData()?->getType());
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $data = $event->getData();

                $this->addValueField($event->getForm(), is_array($data) ? ($data['type'] ?? null) : null);
            }
        );
    }

    /**
     * The 'value' field means different things per discount type: a plain percentage
     * (15 means 15%) for {@see Discount::TYPE_PERCENTAGE}, or an amount in the
     * client's major currency unit (needs converting to minor units) for
     * {@see Discount::TYPE_MONEY}. Only the money case needs the transformer.
     *
     * @param FormInterface<mixed> $form
     */
    private function addValueField(FormInterface $form, ?string $type): void
    {
        $options = [
            'attr' => [
                'class' => 'discount-value',
            ],
        ];

        if (Discount::TYPE_MONEY !== $type) {
            $form->add('value', TextType::class, $options + [
                'empty_data' => '0',
                'constraints' => [
                    new Range(min: 0, max: 100, notInRangeMessage: 'core.constraint.discount_value_percentage_range'),
                ],
            ]);

            return;
        }

        $builder = $form->getConfig()->getFormFactory()->createNamedBuilder('value', TextType::class, null, $options + ['auto_initialize' => false]);
        $builder->addViewTransformer(new DiscountTransformer());

        $form->add($builder->getForm());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Discount::class);
        $resolver->setDefault('currency', $this->systemConfig->getCurrency());
        $resolver->setAllowedTypes('currency', [Currency::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'discount';
    }
}
