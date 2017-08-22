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

use Carbon\Carbon;
use SolidInvoice\CronBundle\Form\Type\CronType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurringInvoiceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('frequency', CronType::class);

        $now = Carbon::now();

        $builder->add(
            'date_start',
            DateType::class,
            [
                'widget' => 'single_text',
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => RecurringInvoice::class]);
    }
}
