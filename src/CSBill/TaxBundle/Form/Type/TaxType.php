<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Form\Type;

use CSBill\CoreBundle\Form\Type\Select2Type;
use CSBill\TaxBundle\Entity\Tax;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add('rate', PercentType::class, ['scale' => 2, 'type' => 'integer']);

        $builder->add(
            'type',
            Select2Type::class,
            [
                'choices' => array_map('ucwords', Tax::getTypes()),
                'help' => 'tax.rates.explanation',
                'placeholder' => 'tax.rates.type.select',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Tax::class]);
    }
}
