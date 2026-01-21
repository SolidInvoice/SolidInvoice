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

use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use Symfony\Component\Uid\Ulid;

final readonly class SendInvoiceReminderMessage
{
    public function __construct(
        public Ulid $invoiceId,
        public Ulid $companyId,
        public ReminderType $reminderType,
        public ?int $daysUntilDue = null,
    ) {
    }
}
