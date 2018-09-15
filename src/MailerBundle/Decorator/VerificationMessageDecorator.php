<?php

declare(strict_types=1);

/*
 * This file is part of the SwiftMailerHandler project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2018
 */

namespace SolidInvoice\MailerBundle\Decorator;

use SolidInvoice\MailerBundle\Event\MessageEvent;

interface VerificationMessageDecorator extends MessageDecorator
{
    public function shouldDecorate(MessageEvent $event): bool;
}