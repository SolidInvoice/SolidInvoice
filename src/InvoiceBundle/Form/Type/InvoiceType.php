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

namespace SolidInvoice\InvoiceBundle\Form\Type;

use Money\Currency;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceType extends AbstractType
{
    /**
     * @var Currency
     */
    private $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'client',
            null,
            [
                'attr' => [
                    'class' => 'select2 client-select',
                ],
                'placeholder' => 'invoice.client.choose',
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
                'by_reference' => false,
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

        $builder->addEventSubscriber(new InvoiceUsersSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Invoice::class,
                'currency' => $this->currency,
            ]
        )
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
