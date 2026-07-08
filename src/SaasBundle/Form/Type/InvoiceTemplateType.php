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

namespace SolidInvoice\SaasBundle\Form\Type;

use Override;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function array_flip;
use function array_merge;

/**
 * Design-template picker for the settings page. Choices come straight from
 * the {@see BillingTemplateRegistry} filesystem scan, so newly shipped
 * templates appear automatically. Rendered as a card gallery with live
 * previews by the `invoice_template_widget` block in the settings form theme.
 *
 * @extends AbstractType<mixed>
 * @see \SolidInvoice\SaasBundle\Tests\Form\Type\InvoiceTemplateTypeTest
 */
final class InvoiceTemplateType extends AbstractType
{
    public function __construct(
        private readonly BillingTemplateRegistry $registry,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => array_merge(
                ['Default' => BillingTemplateRegistry::DEFAULT_SLUG],
                array_flip($this->registry->getChoices()),
            ),
            'expanded' => true,
            'multiple' => false,
            // The settings form marks every field as not required, which would
            // make the expanded choice grow an empty placeholder radio — and
            // the picker widget can't build a preview URL for an empty slug.
            // There is no "no selection" state: the default template is a
            // regular choice.
            'placeholder' => false,
            'empty_data' => BillingTemplateRegistry::DEFAULT_SLUG,
            'translation_domain' => false,
            'choice_translation_domain' => false,
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'invoice_template';
    }
}
