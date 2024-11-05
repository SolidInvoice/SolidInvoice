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

use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

final class UrlFormatter implements FormatterInterface
{
    public function __construct(
        private readonly Environment $twig
    ) {
    }

    /**
     * @throws SyntaxError|LoaderError
     */
    public function format(Column $column, mixed $value): string|TranslatableMessage
    {
        return $this->twig->createTemplate('<a href="{{ value }}" target="_blank">{{ value }}</a>')->render(['value' => $value ?? '']);
    }
}
