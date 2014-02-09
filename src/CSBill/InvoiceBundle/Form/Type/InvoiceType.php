<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InvoiceBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use CSBill\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;

class InvoiceType extends AbstractType
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('client', null, array('attr' => array('class' => 'chosen'), 'empty_value' => '--select--'));
        $builder->add('discount', 'percent');
        $builder->add('items', 'collection', array('type' => new ItemType(),
                                                      'allow_add' => true,
                                                      'allow_delete' => true
                ));

        $builder->addEventSubscriber(new InvoiceUsersSubscriber());
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'invoice';
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::setDefaultOptions()
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'CSBill\InvoiceBundle\Entity\Invoice'
        ));
    }
}
