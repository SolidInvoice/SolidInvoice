<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Form\Type;

use CSBill\CoreBundle\Entity\Tax as TaxEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Tax
 *
 * @package CSBill\CoreBundle\Form\Type
 */
class TaxType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add(
            'rate',
            'percent',
            array(
                'precision' => 0,
                'type' => 'integer',
            )
        );

        $builder->add(
            'type',
            'select2',
            array(
                'choices' => TaxEntity::getTypes(),
                'help' => 'tax.rates.explanation',
                'empty_value' => 'tax.rates.type.select',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('data_class' => 'CSBill\CoreBundle\Entity\Tax'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tax';
    }
}
