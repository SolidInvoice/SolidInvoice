<?php

namespace CSBill\CoreBundle\Mailer;

final class MailerEvents
{
    const MAILER_SEND_INVOICE = 'billing.mailer.send_invoice';

    const MAILER_SEND_QUOTE = 'billing.mailer.send_quote';

    const MAILER_SEND = 'billing.mailer.send';
}
