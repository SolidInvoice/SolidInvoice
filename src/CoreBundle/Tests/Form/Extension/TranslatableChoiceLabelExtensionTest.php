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

namespace SolidInvoice\CoreBundle\Tests\Form\Extension;

use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use SolidInvoice\CoreBundle\Form\Extension\TranslatableChoiceLabelExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\TranslatableMessage;

#[AllowMockObjectsWithoutExpectations]
final class TranslatableChoiceLabelExtensionTest extends TypeTestCase
{
    /**
     * @return array<FormTypeExtensionInterface<mixed>>
     */
    #[Override]
    protected function getTypeExtensions(): array
    {
        return [new TranslatableChoiceLabelExtension(new IdentityTranslator())];
    }

    public function testTranslatableLabelsAreResolvedWhenTranslationIsDisabled(): void
    {
        $view = $this->factory->create(ChoiceType::class, null, [
            'choices' => ['a' => 'a', 'b' => 'b'],
            'choice_label' => static fn (string $choice): TranslatableMessage | string => 'a' === $choice
                ? new TranslatableMessage('Label %name%', ['%name%' => 'A'])
                : 'Plain B',
            'choice_translation_domain' => false,
        ])->createView();

        $labels = array_map(static fn (ChoiceView $choice): mixed => $choice->label, $view->vars['choices']);

        self::assertSame(['Label A', 'Plain B'], array_values($labels));
    }

    public function testTranslatableLabelsAreLeftForTheThemeWhenTranslationIsEnabled(): void
    {
        $view = $this->factory->create(ChoiceType::class, null, [
            'choices' => ['a' => 'a'],
            'choice_label' => static fn (): TranslatableMessage => new TranslatableMessage('Label'),
        ])->createView();

        $choice = array_values($view->vars['choices'])[0];
        self::assertInstanceOf(ChoiceView::class, $choice);
        self::assertInstanceOf(TranslatableMessage::class, $choice->label);
    }
}
