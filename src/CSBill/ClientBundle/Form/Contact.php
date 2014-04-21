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

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use CSBill\ClientBundle\Form\Type\ContactDetailType;

class Contact extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $types;

    /**
     * @param EntityManager $entityManager
     * @param array         $types
     */
    public function __construct(EntityManager $entityManager, array $types)
    {
        $this->entityManager = $entityManager;
        $this->types = $types;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstname');
        $builder->add('lastname');

        foreach ($this->types as $item) {
            /** @var \CSBill\ClientBundle\Entity\ContactType $item */
            $builder->add(
                'details_' . $item->getName(),
                new ContactDetailType,
                array(
                    'type' => new ContactDetail($this->entityManager, $item),
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'label' => 'contact_details',
                    'prototype' => true,
                    'prototype_name' => '__contact_details_prototype__',
                    'error_bubbling' => false
                )
            );
        }

        $types = $this->types;

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($types) {

                $details = $event->getData()->getDetails();

                $detailTypes = array();

                foreach ($details as $detail) {
                    $type = $detail->getType()->getName();
                    $detailTypes[] = $type;
                }

                foreach ($types as $type) {
                    if ($type->isRequired()) {
                        if (!in_array($type->getName(), $detailTypes)) {
                            $name = $type->getName();
                            $error = sprintf(
                                '%s is required',
                                ucwords(str_replace('_', ' ', $name))
                            );
                            $event->getForm()->addError(new FormError($error));
                        }
                    }
                }
            }
        );
    }

    public function getName()
    {
        return 'contact';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'CSBill\ClientBundle\Entity\Contact',
                'csrf_protection'=> false
        ));
    }
}
