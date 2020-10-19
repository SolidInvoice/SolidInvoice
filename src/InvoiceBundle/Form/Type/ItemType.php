<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Form\Type\TaxEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    /**
     * @var \Doctrine\Persistence\ManagerRegistry
     */
    private $registry;

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
                    'class' => 'input-medium invoice-item-name',
                ],
            ]
        );

        $builder->add(
            'price',
            MoneyType::class,
            [
                'attr' => [
                    'class' => 'input-small invoice-item-price',
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
                    'class' => 'input-mini invoice-item-qty',
                ],
            ]
        );

        if ($this->registry->getManager()->getRepository(Tax::class)->taxRatesConfigured()) {
            $builder->add(
                'tax',
                TaxEntityType::class,
                [
                    'class' => Tax::class,
                    'placeholder' => 'No Tax',
                    'attr' => [
                        'class' => 'select2 input-mini invoice-item-tax',
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
        return 'invoice_item';
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
