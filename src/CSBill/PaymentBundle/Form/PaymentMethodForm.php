<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PaymentMethodForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');

        $builder->add('enabled', null, array('required' => false));

        $builder->add(
            'public',
            null,
            array(
                'label' => 'Available for client use',
                'help' => 'payment_public_help_text',
                'help_type' => 'block',
                'required' => false,
            )
        );

        $builder->add(
            'defaultStatus',
            null,
            array(
                'label' => 'payment.create.force',
                'help' => 'force_payment_status_help',
                'help_type' => 'block',
                'placeholder' => 'choose_force_status',
                'empty_data' => null,
                'attr' => array(
                    'class' => 'select2',
                ),
            )
        );

        if (null !== $options['settings']) {
            $builder->add('settings', $options['settings']);
        }

        $builder->add(
            'save',
            'submit',
            array(
                'attr' => array(
                    'class' => 'btn-success'
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('settings'));
        $resolver->setAllowedTypes(array('settings' => array('string', 'null')));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'payment_methods';
    }
}
