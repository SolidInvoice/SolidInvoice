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

namespace SolidInvoice\TaxBundle\Form\Type;

use Override;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<InvoiceTax>
 */
final class InvoiceTaxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tax', EntityType::class, [
                'class' => Tax::class,
                'placeholder' => 'Select a tax',
                'required' => true,
                'choice_label' => static function (Tax $tax): string {
                    $rate = $tax->getRate() ?? 0;
                    $category = $tax->getCategory();
                    $rateLabel = $tax->getType() === Tax::TYPE_FLAT_RATE ? (string) $rate : $rate . '%';
                    $base = sprintf('%s (%s)', $tax->getName() ?? '', $rateLabel);

                    return match ($category) {
                        TaxCategory::Exempt => $base . ' [exempt]',
                        TaxCategory::OutOfScope => $base . ' [out of scope]',
                        TaxCategory::ZeroRated => $base . ' [zero-rated]',
                        TaxCategory::ReverseCharge => $base . ' [reverse charge]',
                        TaxCategory::Standard => $base,
                    };
                },
                'attr' => [
                    'class' => 'invoice-tax-select',
                    'data-invoice-tax-target' => 'tax',
                ],
            ])
            ->add('direction', EnumType::class, [
                'class' => TaxDirection::class,
                'required' => true,
                'placeholder' => false,
                'empty_data' => TaxDirection::Additive->value,
                'choice_label' => static fn (TaxDirection $direction): string => $direction->getLabel(),
                'attr' => [
                    'data-invoice-tax-target' => 'direction',
                ],
            ])
            ->add('note', TextType::class, [
                'required' => false,
                'label' => 'Note',
                'attr' => [
                    'data-invoice-tax-target' => 'note',
                    'placeholder' => 'Optional note (e.g. reverse-charge VAT notice)',
                ],
            ])
            ->add('sequence', HiddenType::class, [
                'required' => false,
                'empty_data' => '0',
                'attr' => [
                    'data-invoice-tax-target' => 'sequence',
                ],
            ]);

        // Populate snapshot fields (name/rate/category) from the selected Tax
        // entity so newly-bound InvoiceTax rows pass NotBlank validation and
        // persist with the rate frozen at the time of selection.
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $invoiceTax = $event->getData();
            if (! $invoiceTax instanceof InvoiceTax) {
                return;
            }

            $tax = $invoiceTax->getTax();
            if (! $tax instanceof Tax) {
                return;
            }

            if ($invoiceTax->getNameSnapshot() !== null && $invoiceTax->getNameSnapshot() !== '') {
                return;
            }

            $invoiceTax->snapshotFrom($tax);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvoiceTax::class,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'invoice_tax';
    }
}
