<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Enum\Menu;

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

enum MenuPriority: int
{
    case PRIORITY_DASHBOARD = 100;
    case PRIORITY_CLIENT = 90;
    case PRIORITY_INVOICE = 80;
    case PRIORITY_RECURRING_INVOICE = 70;
    case PRIORITY_QUOTE = 60;
    case PRIORITY_PAYMENT = 50;
    case PRIORITY_SYSTEM = 10;
}
