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

namespace SolidInvoice\InvoiceBundle\Enum;

/**
 * How a recurring invoice derives the billing period it states on each generated invoice.
 *
 * `None` is the default so that every existing recurring invoice keeps its exact present
 * behaviour after upgrade: no period, on every invoice it generates, until an operator
 * opts in.
 */
enum BillingPeriodMode: string
{
    case None = 'none';
    case Current = 'current';
    case Previous = 'previous';

    public function getLabel(): string
    {
        return 'billing.period.mode.' . $this->value;
    }
}
