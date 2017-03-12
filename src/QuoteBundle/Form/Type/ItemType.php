<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\Type;

use CSBill\QuoteBundle\Entity\Item;
use CSBill\TaxBundle\Form\Type\TaxEntityType;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'description',
            TextareaType::class,
            [
                'attr' => [
                    'class' => 'input-medium quote-item-name',
                ],
            ]
        );

        $builder->add(
            'price',
            MoneyType::class,
            [
                'attr' => [
                    'class' => 'input-small quote-item-price',
                ],
                'currency' => $options['currency'],
            ]
        );

        $builder->add(
            'qty',
            NumberType::class,
            [
                'empty_data' => 1,
                'attr' => [
                    'class' => 'input-mini quote-item-qty',
                ],
            ]
        );

        if ($this->registry->getRepository('CSBillTaxBundle:Tax')->taxRatesConfigured()) {
            $builder->add(
                'tax',
                TaxEntityType::class,
                [
                    'class' => 'CSBill\TaxBundle\Entity\Tax',
                    'placeholder' => 'Choose Tax Type',
                    'attr' => [
                        'class' => 'select2 input-mini quote-item-tax',
                    ],
                    'required' => false,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'quote_item';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Item::class)
            ->setRequired('currency')
            ->setAllowedTypes('currency', ['string']);
    }
}
