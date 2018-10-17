<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Decorator;

use SolidInvoice\MailerBundle\Event\MessageEvent;

interface VerificationMessageDecorator extends MessageDecorator
{
    public function shouldDecorate(MessageEvent $event): bool;
}
