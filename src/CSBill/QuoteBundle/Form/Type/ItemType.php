<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\QuoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ItemType extends AbstractType
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //$builder->add('code', null, array('mapped' => false, 'attr' => array('class' => 'input-mini quote-item-code')));
        $builder->add('description', 'textarea', array('attr' => array('class' => 'input-medium quote-item-name')));
        $builder->add('price', 'money', array('attr' => array('class' => 'input-small quote-item-price')));
        //$builder->add('tax', null, array('mapped' => false, 'attr' => array('class' => 'input-small')));
        $builder->add('qty', 'number', array('data' => 1, 'attr' => array('class' => 'input-mini quote-item-qty')));
        $builder->add('total', 'money', array('mapped' => false, 'attr' => array('class' => 'input-small quote-item-total', 'disabled' => true, 'readonly' => true)));
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'item';
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::setDefaultOptions()
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'CSBill\QuoteBundle\Entity\Item'
        ));
    }
}
