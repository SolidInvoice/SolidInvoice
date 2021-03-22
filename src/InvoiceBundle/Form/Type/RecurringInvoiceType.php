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

use Carbon\CarbonImmutable;
use Money\Currency;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CronBundle\Form\Type\CronType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurringInvoiceType extends AbstractType
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

        $builder->add('frequency', CronType::class);

        $now = CarbonImmutable::now();

        $builder->add(
            'date_start',
            DateType::class,
            [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $now,
                'label' => 'invoice.recurring.date_start',
                'attr' => [
                    'class' => 'datepicker',
                    'data-min-date' => $now->format('Y-m-d'),
                ],
            ]
        );

        $builder->add(
            'date_end',
            DateType::class,
            [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'invoice.recurring.date_end',
                'help' => 'invoice.recurring.date_end_info',
                'help_type' => 'block',
                'attr' => [
                    'class' => 'datepicker',
                    'data-depends' => 'invoice_recurringInfo_date_start',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'recurring_invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => RecurringInvoice::class,
                'currency' => $this->currency,
            ]
        )
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
