<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use CSBill\ClientBundle\Form\Type\ContactType;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add('website');
        $builder->add('status', null, array('required' => true));
        $builder->add('contacts', 'collection', array('type' => new ContactType(),
                                                      'allow_add' => true,
                                                      'allow_delete' => true,
                                                      'by_reference' => false));
    }

    public function getName()
    {
        return 'client';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'CSBill\ClientBundle\Entity\Client'
        ));
    }
}
