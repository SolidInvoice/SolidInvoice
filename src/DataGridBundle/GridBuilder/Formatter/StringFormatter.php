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
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use Stringable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use function is_bool;
use function is_numeric;
use function is_object;
use function is_string;
use function method_exists;
use function spl_object_hash;

final class StringFormatter implements FormatterInterface
{
    public function __construct(
        private readonly Environment $twig
    ) {
    }

    /**
     * @throws SyntaxError|LoaderError
     */
    public function format(Column $column, mixed $value): string
    {
        $formatter = static function (mixed $value): mixed {
            return $value;
        };

        if ($column instanceof StringColumn) {
            $formatter = $column->getCallback();
            if (null !== ($function = $column->getTwigFunction())) {
                return $this->twig->createTemplate(sprintf('{{ %s(value) }}', $function))->render(['value' => $value]);
            }
        }

        $value = $formatter($value);

        if (is_string($value)) {
            return $value;
        }

        if (is_object($value)) {
            if ($value instanceof Stringable || method_exists($value, '__toString')) {
                return $value->__toString();
            }

            return spl_object_hash($value);
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return '';
    }
}
