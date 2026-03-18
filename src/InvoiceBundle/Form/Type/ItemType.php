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
use SolidInvoice\CoreBundle\Enum\LineItemType;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
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

        $builder->add('lineItemType', EnumType::class, [
            'class' => LineItemType::class,
            'expanded' => true,
            'label' => false,
            'attr' => ['class' => 'line-type-toggle'],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $line = $event->getData();
            if ($line instanceof Line && $line->getLineItemType() === LineItemType::TimeTracking) {
                $this->addDurationQtyField($event->getForm());
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            // Default to Standard if not submitted (e.g. new item with no radio selected)
            if (! isset($data['lineItemType'])) {
                $data['lineItemType'] = LineItemType::Standard->value;
                $event->setData($data);
            }

            if ($data['lineItemType'] === LineItemType::TimeTracking->value) {
                $this->addDurationQtyField($event->getForm());
            }
        });

        if ($this->registry->getManager()->getRepository(Tax::class)->taxRatesConfigured()) {
            $builder->add(
                'tax',
                EntityType::class,
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

    private function addDurationQtyField(FormInterface $form): void
    {
        $form->remove('qty');
        $form->add('qty', DurationQtyType::class, [
            'label' => 'Duration',
            'attr' => [
                'class' => 'input-mini',
            ],
        ]);
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
