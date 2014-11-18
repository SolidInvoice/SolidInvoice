<?php
/**
 * This file is part of the MiWay Business Insurance project.
 *
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
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
        $typeHelpText = <<<HTML
Inclusive: Tax is included in the item price
Exclusive: Tax is calculated on the item price
HTML;

        $builder->add('name');
        $builder->add(
            'rate',
            'percent',
            array(
                'precision' => 2,
                'type' => 'integer',
            )
        );

        $builder->add(
            'type',
            'choice',
            array(
                'choices' => TaxEntity::getTypes(),
                'help' => $typeHelpText,
                'empty_value' => 'select',
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
