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

use CSBill\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceType extends AbstractType
{
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
                    'class' => 'select2',
                ],
                'placeholder' => 'invoice.client.choose',
            ]
        );

        $builder->add('discount', 'percent', ['required' => false]);
        $builder->add('recurring', 'checkbox', ['required' => false, 'label' => 'Recurring']);
        $builder->add('recurringInfo', new RecurringInvoiceType());

        $builder->add(
            'items',
            'collection',
            [
                'type' => 'invoice_item',
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

        $builder->addEventSubscriber(new InvoiceUsersSubscriber());
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (!array_key_exists('recurring', $data) || (int) $data['recurring'] !== 1) {
                unset($data['recurringInfo']);
                $event->setData($data);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => function (FormInterface $form) {
                    $recurring = $form->get('recurring')->getData();

                    if (true === $recurring) {
                        return ['Default', 'Recurring'];
                    }

                    return 'Default';
                },
                'data_class' => 'CSBill\InvoiceBundle\Entity\Invoice',
            ]
        );
    }
}
