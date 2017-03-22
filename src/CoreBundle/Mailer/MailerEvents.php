<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer;

final class MailerEvents
{
    const MAILER_SEND_INVOICE = 'billing.mailer.send_invoice';

    const MAILER_SEND_QUOTE = 'billing.mailer.send_quote';

    const MAILER_SEND = 'billing.mailer.send';
}
