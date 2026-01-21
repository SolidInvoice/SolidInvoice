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

namespace SolidInvoice\InvoiceBundle\Entity;

enum ReminderType: string
{
    case PreDue = 'pre_due';
    case Overdue1 = 'overdue_1';
    case Overdue7 = 'overdue_7';
    case Overdue14 = 'overdue_14';
}
