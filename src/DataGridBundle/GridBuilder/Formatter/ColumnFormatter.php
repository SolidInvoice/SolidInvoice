<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Formatter;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\CurrencyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\UrlColumn;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class ColumnFormatter implements ServiceSubscriberInterface, FormatterInterface
{
    /**
     * @param ContainerInterface<class-string<Column>, FormatterInterface> $locator
     */
    public function __construct(
        private readonly ContainerInterface $locator
    ) {
    }

    /**
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface
     */
    public function format(Column $column, mixed $value): string
    {
        if (! $this->locator->has($column::class)) {
            return $this->locator->get(StringColumn::class)?->format($column, $value);
        }

        return $this->locator->get($column::class)?->format($column, $value);
    }

    public static function getSubscribedServices(): array
    {
        return [
            CurrencyColumn::class => CurrencyFormatter::class,
            DateTimeColumn::class => DateTimeFormatter::class,
            StringColumn::class => StringFormatter::class,
            UrlColumn::class => UrlFormatter::class,
        ];
    }
}
