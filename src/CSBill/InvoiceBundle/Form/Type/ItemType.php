<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Form\Type;

use CSBill\CoreBundle\Repository\TaxRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ItemType extends AbstractType
{
    /**
     * @var TaxRepository
     */
    private $taxRepo;

    /**
     * @param TaxRepository $taxRepo
     */
    public function __construct(TaxRepository $taxRepo)
    {
        $this->taxRepo = $taxRepo;
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'description',
            'textarea',
            array(
                'attr' => array(
                    'class' => 'input-medium invoice-item-name',
                ),
            )
        );

        $builder->add(
            'price',
            'money',
            array(
                'attr' => array(
                    'class' => 'input-small invoice-item-price',
                ),
            )
        );

        $builder->add(
            'qty',
            'number',
            array(
                'data' => 1,
                'attr' => array(
                    'class' => 'input-mini invoice-item-qty',
                ),
            )
        );

        if ($this->taxRepo->getTotal() > 0) {
            $builder->add(
                'tax',
                new \CSBill\CoreBundle\Form\Type\Tax(),
                array(
                    'class' => 'CSBill\CoreBundle\Entity\Tax',
                    'placeholder' => 'Choose Tax Type',
                    'attr' => array(
                        'class' => 'input-mini invoice-item-tax',
                    ),
                    'required' => false,
                )
            );
        }
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'invoice_item';
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::setDefaultOptions()
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CSBill\InvoiceBundle\Entity\Item',
            )
        );
    }
}
