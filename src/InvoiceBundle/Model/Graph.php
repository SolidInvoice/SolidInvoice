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

namespace SolidInvoice\InvoiceBundle\Model;

final class Graph
{
    public const string TRANSITION_ACCEPT = 'accept';

    public const string TRANSITION_ACTIVATE = 'activate';

    public const string TRANSITION_NEW = 'new';

    public const string TRANSITION_CANCEL = 'cancel';

    public const string TRANSITION_OVERDUE = 'overdue';

    public const string TRANSITION_PAY = 'pay';

    public const string TRANSITION_REOPEN = 'reopen';

    public const string TRANSITION_ARCHIVE = 'archive';
}
