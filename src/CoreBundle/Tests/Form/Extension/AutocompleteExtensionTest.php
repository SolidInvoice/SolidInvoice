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
use SolidInvoice\CoreBundle\Form\Extension\AutocompleteExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\Test\TypeTestCase;

#[AllowMockObjectsWithoutExpectations]
final class AutocompleteExtensionTest extends TypeTestCase
{
    /**
     * @return array<FormTypeExtensionInterface<mixed>>
     */
    #[Override]
    protected function getTypeExtensions(): array
    {
        return [new AutocompleteExtension()];
    }

    public function testChoicesAreAutocompletedByDefault(): void
    {
        $form = $this->factory->create(ChoiceType::class, null, ['choices' => ['A' => 'a']]);

        self::assertTrue($form->getConfig()->getOption('autocomplete'));
    }

    public function testExpandedChoicesAreNotAutocompleted(): void
    {
        // The autocomplete controller only works on an <input> or <select>, and an expanded
        // choice renders as a div of radios, so it must not be switched on for those.
        $form = $this->factory->create(ChoiceType::class, null, ['choices' => ['A' => 'a'], 'expanded' => true]);

        self::assertFalse($form->getConfig()->getOption('autocomplete'));
    }

    public function testAnExplicitOptionStillWins(): void
    {
        $form = $this->factory->create(ChoiceType::class, null, [
            'choices' => ['A' => 'a'],
            'expanded' => true,
            'autocomplete' => true,
        ]);

        self::assertTrue($form->getConfig()->getOption('autocomplete'));
    }
}
