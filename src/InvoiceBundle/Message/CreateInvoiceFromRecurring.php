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

namespace SolidInvoice\InvoiceBundle\Message;

use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;

final class CreateInvoiceFromRecurring
{
    public function __construct(
        private readonly RecurringInvoice $recurringInvoice
    ) {
    }

    public function getRecurringInvoice(): RecurringInvoice
    {
        return $this->recurringInvoice;
    }
}
