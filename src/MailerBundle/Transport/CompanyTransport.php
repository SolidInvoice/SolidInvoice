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

namespace SolidInvoice\MailerBundle\Transport;

use Closure;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Resolves the transport for the active company on every send, so a long-running
 * process never reuses one company's mail settings for another.
 */
final readonly class CompanyTransport implements TransportInterface
{
    /**
     * @param Closure(): TransportInterface $resolve
     */
    public function __construct(
        private Closure $resolve
    ) {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        return ($this->resolve)()->send($message, $envelope);
    }

    public function __toString(): string
    {
        return 'solidinvoice://company';
    }
}
