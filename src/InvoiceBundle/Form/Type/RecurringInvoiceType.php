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
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CronBundle\Form\Type\CronType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\RecurringInvoiceTypeTest
 */
class RecurringInvoiceType extends AbstractType
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly ManagerRegistry $registry
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'client',
            null,
            [
                'attr' => [
                    'class' => 'client-select',
                ],
                'placeholder' => 'invoice.client.choose',
                'choices' => $this->registry->getRepository(Client::class)->findAll()
            ]
        );

        $builder->add('discount', DiscountType::class, ['required' => false, 'label' => 'Discount', 'currency' => $options['currency']]);

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
                    'currency' => $options['currency'],
                ],
            ]
        );

        $builder->add('terms');
        $builder->add('notes', null, ['help' => 'Notes will not be visible to the client']);
        $builder->add('total', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('baseTotal', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('tax', HiddenMoneyType::class, ['currency' => $options['currency']]);

        $builder->addEventSubscriber(new InvoiceUsersSubscriber($builder, $options['data'], $this->registry));

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
                'input' => 'datetime_immutable',
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

    public function getBlockPrefix(): string
    {
        return 'recurring_invoice';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(
                [
                    'data_class' => RecurringInvoice::class,
                    'currency' => $this->systemConfig->getCurrency(),
                ]
            )
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
