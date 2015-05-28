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

use CSBill\TaxBundle\Form\Type\Tax;
use CSBill\TaxBundle\Repository\TaxRepository;
use Doctrine\ORM\EntityManagerInterface;
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
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->taxRepo = $entityManager->getRepository('CSBillTaxBundle:Tax');
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
                new Tax(),
                array(
                    'class' => 'CSBill\TaxBundle\Entity\Tax',
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'invoice_item';
    }

    /**
     * {@inheritdoc}
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
