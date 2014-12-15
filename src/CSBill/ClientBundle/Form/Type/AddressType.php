<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AddressType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('street1');
        $builder->add('street2');
        $builder->add('city');
        $builder->add('state');
        $builder->add('zip');
        $builder->add(
            'country',
            'country',
            array(
                'attr' => array(
                    'class' => 'select2',
                ),
                'placeholder' => 'client.address.country.select',
            )
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CSBill\ClientBundle\Entity\Address',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'address';
    }
}
