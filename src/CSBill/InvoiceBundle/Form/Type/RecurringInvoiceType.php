<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Form\Type;

use Carbon\Carbon;
use CSBill\CronBundle\Form\Type\CronType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurringInvoiceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('frequency', new CronType());

        $now = Carbon::now();

        $builder->add(
            'date_start',
            'date',
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
            'date',
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
    public function getName()
    {
        return 'recurring_invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'CSBill\InvoiceBundle\Entity\RecurringInvoice',
            ]
        );
    }
}
