<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\Type;

use CSBill\CoreBundle\Repository\TaxRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ItemType extends AbstractType
{
    /**
     * @var TaxRepository
     */
    private $repo;

    /**
     * @param TaxRepository $repo
     */
    public function __construct(TaxRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'description',
            'textarea',
            array(
                'attr' => array(
                    'class' => 'input-medium quote-item-name',
                ),
            )
        );

        $builder->add(
            'price',
            'money',
            array(
                'attr' => array(
                    'class' => 'input-small quote-item-price',
                ),
            )
        );

        $builder->add(
            'qty',
            'number',
            array(
                'data' => 1,
                'attr' => array(
                    'class' => 'input-mini quote-item-qty',
                ),
            )
        );

        if ($this->repo->getTotal() > 0) {
            $builder->add(
                'tax',
                new \CSBill\CoreBundle\Form\Type\Tax(),
                array(
                    'class' => 'CSBill\CoreBundle\Entity\Tax',
                    'empty_value' => 'Choose Tax Type',
                    'attr' => array(
                        'class' => 'input-mini quote-item-tax',
                    ),
                )
            );
        }

        $builder->add(
            'total',
            'money',
            array(
                'mapped' => false,
                'attr' => array(
                    'class' => 'input-small quote-item-total',
                    'disabled' => true,
                    'readonly' => true,
                ),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'quote_item';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CSBill\QuoteBundle\Entity\Item',
            )
        );
    }
}
