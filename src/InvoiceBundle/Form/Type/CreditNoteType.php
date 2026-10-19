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
use SolidInvoice\ClientBundle\Form\ClientAutocompleteType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\CreditNoteTypeTest
 * @extends AbstractType<CreditNote>
 */
class CreditNoteType extends AbstractType
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'client',
            ClientAutocompleteType::class,
            [
                'placeholder' => 'invoice.client.choose',
                'disabled' => $options['client_locked'],
            ]
        );

        $builder->add(
            'discount',
            DiscountType::class,
            [
                'required' => false,
                'label' => 'billing.discount',
                'currency' => $options['currency'],
            ]
        );

        $builder->add(
            'lines',
            LiveCollectionType::class,
            [
                'entry_type' => CreditNoteLineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'entry_options' => [
                    'currency' => $options['currency'],
                ],
            ]
        );

        $builder->add('reason', null, ['required' => false, 'label' => 'credit_note.reason']);
        $builder->add('terms', null, ['label' => 'invoice.terms', 'required' => false]);
        $builder->add('notes', null, ['required' => false, 'help' => 'billing.notes_help']);
        $builder->add('total', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('baseTotal', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('tax', HiddenMoneyType::class, ['currency' => $options['currency']]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'credit_note';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(
                [
                    'data_class' => CreditNote::class,
                    'currency' => $this->systemConfig->getCurrency(),
                    // Locked when the credit note already has a fixed client — standalone
                    // creation lets the user pick one, create-from-invoice does not.
                    'client_locked' => false,
                ]
            )
            ->setAllowedTypes('currency', [Currency::class])
            ->setAllowedTypes('client_locked', ['bool']);
    }
}
