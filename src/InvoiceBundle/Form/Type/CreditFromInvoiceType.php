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

use Money\Currency;
use Override;
use SolidInvoice\InvoiceBundle\DTO\CreditFromInvoiceData;
use SolidInvoice\InvoiceBundle\Enum\CreditFromInvoiceMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The full-or-partial picker for crediting an invoice directly. See `design` §8 on SOL-69.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\CreditFromInvoiceTypeTest
 * @extends AbstractType<CreditFromInvoiceData>
 */
class CreditFromInvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'mode',
            EnumType::class,
            [
                'class' => CreditFromInvoiceMode::class,
                'expanded' => true,
                'label' => false,
                'choice_label' => static fn (CreditFromInvoiceMode $mode): string => 'credit_note.create_from_invoice.mode.' . $mode->value,
            ]
        );

        $builder->add(
            'amount',
            MoneyType::class,
            [
                'required' => false,
                'label' => 'credit_note.create_from_invoice.amount',
                'currency' => $options['currency'],
            ]
        );

        $builder->add(
            'reason',
            TextareaType::class,
            [
                'required' => false,
                'label' => 'credit_note.reason',
            ]
        );
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'credit_from_invoice';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('data_class', CreditFromInvoiceData::class)
            ->setRequired('currency')
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
