<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form;

use CSBill\ClientBundle\Form\Type\ContactDetailType;
use CSBill\ClientBundle\Form\Type\PrimaryContactDetailType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Contact extends AbstractType
{
    /**
     * @var \CSBill\ClientBundle\Entity\ContactType[]
     */
    protected $types;

    /**
     * @param array $types
     */
    public function __construct(array $types = [])
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstname');
        $builder->add('lastname');

        foreach ($this->types as $item) {
            if ($item->isRequired()) {
                $contactDetails = $builder->create(
                    'details_'.$item->getName(),
                    new PrimaryContactDetailType($item),
                    [
                        'required' => true,
                        'property_path' => 'primaryDetails',
                        'by_reference' => true,
                    ]
                );

                $contactDetails->addModelTransformer(new DataTransformer\ContactDetailTransformer($item));

                $builder->add(
                    $contactDetails
                );
            }
        }

        $builder->add(
            'additionalDetails',
            new ContactDetailType(),
            [
                'type' => new ContactDetail($this->types),
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'required' => false,
                'by_reference' => false,
                'label' => 'contact_details',
                'prototype' => true,
                'prototype_name' => '__contact_details_prototype__',
                'error_bubbling' => false,
                'options' => [
                    'data_class' => 'CSBill\ClientBundle\Entity\AdditionalContactDetail',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_delete'] = $options['allow_delete'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'CSBill\ClientBundle\Entity\Contact',
                'csrf_protection' => false,
                'allow_delete' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'contact';
    }
}
