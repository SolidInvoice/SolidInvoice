<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Form;

use Symfony\Component\Form\FormView;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FieldRenderer
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param FormView $form
     * @param string   $path
     *
     * @return array
     */
    public function render(FormView $form, string $path = null): array
    {
        $items = [];

        if (!$path) {
            $path = 'children';
        }

        $propertyAccess = PropertyAccess::createPropertyAccessor();

        foreach ($propertyAccess->getValue($form, $path) as $name => $item) {
            $items[$name] = $this->twig->createTemplate('{{ form_widget(item) }}')->render(['item' => $item]);
        }

        return $items;
    }
}
