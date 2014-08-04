<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use CSBill\QuoteBundle\Form\EventListener\QuoteUsersSubscriber;

class QuoteType extends AbstractType
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'client',
            null,
            array(
                'attr' => array(
                    'class' => 'select2'
                ),
                'empty_value' => 'choose_client'
            )
        );

        $builder->add('discount', 'percent');
        $builder->add(
            'items',
            'collection',
            array(
                'type' => new ItemType(),
                'allow_add' => true,
                'allow_delete' => true
            )
        );

        $builder->add('terms');
        $builder->add('notes', null, array('help' => 'Notes will not be visible to the client'));

        $builder->addEventSubscriber(new QuoteUsersSubscriber());
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'quote';
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::setDefaultOptions()
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CSBill\QuoteBundle\Entity\Quote'
        ));
    }
}
