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

namespace SolidInvoice\CoreBundle\Form\Extension;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Resolves translatable choice labels on choice fields that have translation switched off.
 *
 * With `choice_translation_domain: false` (the EntityType default), the form theme prints
 * `choice.label` raw, without going through the `trans` filter. A label that is a
 * TranslatableInterface (e.g. a TranslatableMessage) then cannot be cast to a string and
 * rendering fails. Translating those labels up front means the theme always gets a string.
 *
 * The low priority makes this run after other choice extensions (e.g. ux-autocomplete).
 *
 * @see \SolidInvoice\CoreBundle\Tests\Form\Extension\TranslatableChoiceLabelExtensionTest
 */
#[AutoconfigureTag('form.type_extension', attributes: ['priority' => -100])]
final class TranslatableChoiceLabelExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param FormInterface<mixed> $form
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (false !== ($options['choice_translation_domain'] ?? null)) {
            return;
        }

        foreach (['choices', 'preferred_choices'] as $key) {
            if (isset($view->vars[$key]) && \is_array($view->vars[$key])) {
                $view->vars[$key] = $this->resolveLabels($view->vars[$key]);
            }
        }
    }

    public static function getExtendedTypes(): iterable
    {
        yield ChoiceType::class;
    }

    /**
     * @param array<array-key, mixed> $choices
     *
     * @return array<array-key, mixed>
     */
    private function resolveLabels(array $choices): array
    {
        foreach ($choices as $key => $choice) {
            if ($choice instanceof ChoiceGroupView) {
                $choices[$key] = new ChoiceGroupView($choice->label, $this->resolveLabels($choice->choices));

                continue;
            }

            if ($choice instanceof ChoiceView && $choice->label instanceof TranslatableInterface) {
                $choices[$key] = new ChoiceView(
                    $choice->data,
                    $choice->value,
                    $choice->label->trans($this->translator),
                    $choice->attr,
                    $choice->labelTranslationParameters,
                );
            }
        }

        return $choices;
    }
}
