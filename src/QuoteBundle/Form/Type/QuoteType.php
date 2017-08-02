<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\Type;

use CSBill\CoreBundle\Form\EventListener\BillingFormSubscriber;
use CSBill\CoreBundle\Form\Type\DiscountType;
use CSBill\MoneyBundle\Form\Type\HiddenMoneyType;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Form\EventListener\QuoteUsersSubscriber;
use Money\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteType extends AbstractType
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param Currency $currency
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'client',
            null,
            [
                'attr' => [
                    'class' => 'select2 client-select',
                ],
                'placeholder' => 'quote.client.choose',
            ]
        );

        $builder->add('discount', DiscountType::class, ['required' => false, 'label' => 'Discount', 'currency' => $options['currency']->getCode()]);

        $builder->add(
            'items',
            CollectionType::class,
            [
                'entry_type' => ItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'required' => false,
                'entry_options' => [
                    'currency' => $options['currency']->getCode(),
                ],
            ]
        );

        $builder->add('terms');
        $builder->add('notes', null, ['help' => 'Notes will not be visible to the client']);
        $builder->add('total', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('baseTotal', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('tax', HiddenMoneyType::class, ['currency' => $options['currency']]);

        $builder->addEventSubscriber(new QuoteUsersSubscriber());
        $builder->addEventSubscriber(new BillingFormSubscriber($options['currency']));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Quote::class,
                'currency' => $this->currency,
            ]
        )
            ->setAllowedTypes('currency', [Currency::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'quote';
    }
}
