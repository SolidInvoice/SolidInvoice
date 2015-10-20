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

        $builder->add(
            'date_start',
            'date',
            [
                'widget' => 'single_text',
                'data' => new \DateTime(),
                'attr' => [
                    'class' => 'datepicker',
                ],
            ]
        );

        $builder->add(
            'date_end',
            'date',
            [
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker',
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
            array(
                'data_class' => 'CSBill\InvoiceBundle\Entity\RecurringInvoice',
            )
        );
    }
}
