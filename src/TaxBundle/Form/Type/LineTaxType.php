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
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<LineTax>
 */
final class LineTaxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tax', EntityType::class, [
                'class' => Tax::class,
                'placeholder' => 'No Tax',
                'required' => false,
                'choice_label' => static function (Tax $tax): string {
                    $rate = $tax->getRate() ?? 0;
                    $category = $tax->getCategory();
                    $compound = $tax->isCompound() ? ', compound' : '';
                    $rateLabel = $tax->getType() === Tax::TYPE_FLAT_RATE ? (string) $rate : $rate . '%';
                    $base = sprintf('%s (%s%s)', $tax->getName() ?? '', $rateLabel, $compound);

                    return match ($category) {
                        TaxCategory::Exempt => $base . ' [exempt]',
                        TaxCategory::OutOfScope => $base . ' [out of scope]',
                        TaxCategory::ZeroRated => $base . ' [zero-rated]',
                        TaxCategory::ReverseCharge => $base . ' [reverse charge]',
                        TaxCategory::Standard => $base,
                    };
                },
                'attr' => [
                    'class' => 'line-tax-select',
                    'data-line-tax-target' => 'tax',
                ],
            ])
            ->add('sequence', HiddenType::class, [
                'required' => false,
                'empty_data' => '0',
                'attr' => [
                    'data-line-tax-target' => 'sequence',
                ],
            ]);

        // Populate snapshot fields (name/rate/category/type/compound) from the selected
        // Tax entity so newly-bound LineTax rows pass NotBlank validation and persist
        // with the rate frozen at the time of selection.
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $lineTax = $event->getData();
            if (! $lineTax instanceof LineTax) {
                return;
            }

            $tax = $lineTax->getTax();
            if (! $tax instanceof Tax) {
                return;
            }

            if ($lineTax->getNameSnapshot() !== null && $lineTax->getNameSnapshot() !== '') {
                return;
            }

            $lineTax->snapshotFrom($tax);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LineTax::class,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'line_tax';
    }
}
