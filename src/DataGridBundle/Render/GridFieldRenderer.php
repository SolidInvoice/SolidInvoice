<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Render;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\ColumnFormatter;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

class GridFieldRenderer
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ColumnFormatter $columnFormatter,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function render(Column $column, object $entity): string
    {
        try {
            $value = $this->propertyAccessor->getValue($entity, $column->getField());
        } catch (NoSuchPropertyException) {
            $value = '';
        }

        $value = $this->columnFormatter->format($column, $column->getFormatValue()($value, $entity));

        if ($value instanceof TranslatableInterface) {
            return $value->trans($this->translator);
        }

        if ($column->getLink() === null && $column->getLinkRoute() === null) {
            return $value;
        }

        return $this->renderLink($column, $entity, $value);
    }

    /**
     * @throws SyntaxError
     * @throws LoaderError
     */
    private function renderLink(Column $column, object $entity, string $value): string
    {
        $url = $column->getLink();

        if ($route = $column->getLinkRoute()) {
            $parameters = [];

            foreach ($column->getLinkParameters() as $key => $field) {
                try {
                    $parameters[$key] = $this->propertyAccessor->getValue($entity, $field);
                } catch (NoSuchPropertyException) {
                    $parameters[$key] = $field;
                }
            }

            $url = $this->router->generate($route, $parameters);
        }

        return $this->twig->createTemplate('<a href="{{ url }}" target="_blank">{{ value }}</a>')->render(['url' => $url, 'value' => $value]);
    }
}
