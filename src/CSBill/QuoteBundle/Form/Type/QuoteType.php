<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CS\QuoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use CS\ClientBundle\Form\Type\ContactType;

class QuoteType extends AbstractType
{
	/**
	 * (non-PHPdoc)
	 * @see Symfony\Component\Form.AbstractType::buildForm()
	 */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('client');
        $builder->add('status', null, array('required' => true));
        $builder->add('items', 'collection', array('type' => new ItemType(),
                                                      'allow_add' => true,
                                                      'allow_delete' => true,
                                                      'by_reference' => false));
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
                'data_class' => 'CS\QuoteBundle\Entity\Quote'
        ));
    }
}
