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

namespace SolidInvoice\InvoiceBundle\Twig\Extension;

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Twig\Attribute\AsTwigFunction;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Twig\Extension\BillingPeriodExtensionTest
 */
final class BillingPeriodExtension
{
    /**
     * Returns '' when the invoice has no period, so this is safe to call
     * unconditionally in every template.
     */
    #[AsTwigFunction(name: 'billing_period')]
    public function formatBillingPeriod(Invoice $invoice): string
    {
        $period = $invoice->getPeriod();

        if (! $period->isComplete()) {
            return '';
        }

        $start = $period->getStart();
        $end = $period->getEnd();

        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $end->format('j F Y');
        }

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return sprintf('%s–%s %s', $start->format('j'), $end->format('j'), $end->format('F Y'));
        }

        if ($start->format('Y') === $end->format('Y')) {
            return sprintf('%s – %s %s', $start->format('j F'), $end->format('j F'), $end->format('Y'));
        }

        return sprintf('%s – %s', $start->format('j F Y'), $end->format('j F Y'));
    }
}
