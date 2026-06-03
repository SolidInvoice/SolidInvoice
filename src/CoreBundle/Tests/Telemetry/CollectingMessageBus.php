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

namespace SolidInvoice\CoreBundle\Tests\Telemetry;

use SolidInvoice\CoreBundle\Telemetry\Message\SendTelemetryMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Test double that records every dispatched {@see SendTelemetryMessage}.
 */
final class CollectingMessageBus implements MessageBusInterface
{
    /**
     * @var list<SendTelemetryMessage>
     */
    public array $messages = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if ($message instanceof SendTelemetryMessage) {
            $this->messages[] = $message;
        }

        return new Envelope($message);
    }
}
