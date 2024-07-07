<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use SolidInvoice\CoreBundle\Form\Type\UuidEntityType;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\ItemTypeTest
 */
class ItemType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                UuidEntityType::class,
                [
                    'class' => Tax::class,
                    'placeholder' => 'No Tax',
                    'attr' => [
                        'class' => 'input-mini invoice-item-tax',
                    ],
                    'required' => false,
                ]
            );
        }
    }

    public function getBlockPrefix(): string
    {
        return 'invoice_item';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('data_class', Line::class)
            ->setRequired('currency')
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
