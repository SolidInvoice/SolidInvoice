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

namespace SolidInvoice\CoreBundle\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use function array_splice;
use function array_values;
use function is_array;

/**
 * Drag-to-reorder for the line editor of {@see \SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice},
 * {@see \SolidInvoice\InvoiceBundle\Twig\Components\CreateRecurringInvoice} and
 * {@see \SolidInvoice\QuoteBundle\Twig\Components\CreateQuote}.
 */
trait ReordersLines
{
    /**
     * Moves the line at $from to $to.
     *
     * The server owns the order rather than the browser: the drag reorders the submitted
     * values and the component re-renders from them, so the rows survive the next re-render —
     * which a reordered DOM on its own would not. The form manager turns the resulting order
     * into positions on save.
     */
    #[LiveAction]
    public function moveLine(#[LiveArg] int $from, #[LiveArg] int $to): void
    {
        $lines = $this->formValues['lines'] ?? null;

        if (! is_array($lines)) {
            return;
        }

        // Keys carry no meaning here — the collection form reads the entries in order — but
        // they have to be a list for the splices below to address the right rows.
        $lines = array_values($lines);

        if (! isset($lines[$from], $lines[$to])) {
            return;
        }

        array_splice($lines, $to, 0, array_splice($lines, $from, 1));

        $this->formValues['lines'] = $lines;
    }
}
