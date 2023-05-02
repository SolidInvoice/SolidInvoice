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

namespace SolidInvoice\CoreBundle\Form;

use Symfony\Component\Form\FormView;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Environment;

class FieldRenderer
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(FormView $form, string $path = null): array
    {
        $items = [];

        if (! $path) {
            $path = 'children';
        }

        $propertyAccess = PropertyAccess::createPropertyAccessor();

        foreach ($propertyAccess->getValue($form, $path) as $name => $item) {
            $items[$name] = $this->twig->createTemplate('{{ form_widget(item) }}')->render(['item' => $item]);
        }

        return $items;
    }
}
