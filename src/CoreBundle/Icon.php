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

namespace SolidInvoice\CoreBundle;

class Icon
{
    final public const string CLIENT = 'users-group';

    final public const string INVOICE = 'file-invoice';

    final public const string QUOTE = 'file-text';

    final public const string PAYMENT = 'credit-card';

    final public const string CLIENT_ADD = 'user-plus';

    final public const string QUOTE_ADD = 'file-plus';

    final public const string INVOICE_ADD = 'file-plus';

    final public const string RECURRING_INVOICE = 'rotate-2';

    final public const string RECURRING_INVOICE_ADD = 'text-plus';
}
