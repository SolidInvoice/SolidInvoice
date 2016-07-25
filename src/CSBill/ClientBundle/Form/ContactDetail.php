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

use CSBill\ClientBundle\Form\DataTransformer\ContactTypeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContactDetail extends AbstractType
{
    /**
     * @var \CSBill\ClientBundle\Entity\ContactType[]
     */
    protected $types;

    /**
     * @param \CSBill\ClientBundle\Entity\ContactType[] $types
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
        $options = [
            'data_class' => null,
            'attr' => [
                'class' => 'input-group-select-val',
            ],
        ];

        $builder->add(
            $builder
                ->create('type', HiddenType::class, $options)
                ->addModelTransformer(new ContactTypeTransformer($this->types))
        );

        $builder->add('value', TextType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact_detail';
    }
}
