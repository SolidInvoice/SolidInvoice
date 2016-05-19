<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\Type;

use CSBill\CoreBundle\Form\EventListener\BillingFormSubscriber;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Form\EventListener\QuoteUsersSubscriber;
use Money\Currency;
use Symfony\Component\Form\AbstractType;
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

        $builder->add('discount', 'percent', ['required' => false]);

        $builder->add(
            'items',
            'collection',
            [
                'type' => 'quote_item',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ]
        );

        $builder->add('terms');
        $builder->add('notes', null, ['help' => 'Notes will not be visible to the client']);
        $builder->add('total', 'hidden_money');
        $builder->add('baseTotal', 'hidden_money');
        $builder->add('tax', 'hidden_money');

        $builder->addEventSubscriber(new QuoteUsersSubscriber());
        $builder->addEventSubscriber(new BillingFormSubscriber($this->currency));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => 'CSBill\QuoteBundle\Entity\Quote']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'quote';
    }
}
