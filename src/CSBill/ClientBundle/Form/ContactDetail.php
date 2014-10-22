<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form;

use CSBill\ClientBundle\Form\DataTransformer\ContactTypeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ContactDetail extends AbstractType
{
    /**
     * @var array
     */
    protected $types;

    /**
     * @param array $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = array(
            'data_class' => null,
            'attr' => array(
                'class' => 'input-group-select-val'
            )
        );

        $builder->add(
            $builder
                ->create('type', 'hidden', $options)
                ->addModelTransformer(new ContactTypeTransformer($this->types))
        );

        $builder->add('value', 'text');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'contact_detail';
    }
}
