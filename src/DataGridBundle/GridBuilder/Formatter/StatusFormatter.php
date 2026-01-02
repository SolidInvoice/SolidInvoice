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

namespace SolidInvoice\DataGridBundle\GridBuilder\Formatter;

use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StatusColumn;
use Symfony\Contracts\Translation\TranslatorInterface;
use function htmlspecialchars;
use function sprintf;
use function strtolower;
use function ucwords;

/**
 * Formats status values as styled badges.
 *
 * Output:
 *   <span class="datagrid-status status-{variant}">{Label}</span>
 */
final readonly class StatusFormatter implements FormatterInterface
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function format(Column $column, mixed $value): string
    {
        if (! $column instanceof StatusColumn) {
            return (string) $value;
        }

        if ($value === null || $value === '') {
            return '';
        }

        $stringValue = (string) $value;
        $variant = $column->getVariantForStatus($stringValue);
        $label = $column->isTitleCase()
            ? ucwords(strtolower(str_replace(['_', '-'], ' ', $stringValue)))
            : $stringValue;

        // Translate the label if it's a known status
        $translatedLabel = $this->translator->trans($label);
        $escapedLabel = htmlspecialchars($translatedLabel, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<span class="datagrid-status status-%s">%s</span>',
            htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'),
            $escapedLabel
        );
    }
}
