<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Notification;

use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Transport\TransportInterface;
use function array_keys;
use function implode;
use function sprintf;

final class Transports implements TransportInterface
{
    /**
     * @param iterable<string, TransportInterface> $transports
     */
    public function __construct(
        private readonly iterable $transports
    ) {
    }

    public function __toString(): string
    {
        $transports = [];
        foreach ($this->transports as $name => $transport) {
            $transports[] = $name;
        }

        return '[' . implode(',', array_keys($transports)) . ']';
    }

    public function supports(MessageInterface $message): bool
    {
        foreach ($this->transports as $transport) {
            if ($transport->supports($message)) {
                return true;
            }
        }

        return false;
    }

    public function send(MessageInterface $message): SentMessage
    {
        if (! $transport = $message->getTransport()) {
            foreach ($this->transports as $transport) {
                if ($transport->supports($message)) {
                    return $transport->send($message);
                }
            }
            throw new LogicException(sprintf('None of the available transports support the given message (available transports: "%s").', implode('", "', array_keys($this->transports))));
        }

        if (! isset($this->transports[$transport])) {
            throw new InvalidArgumentException(sprintf('The "%s" transport does not exist (available transports: "%s").', $transport, implode('", "', array_keys($this->transports))));
        }

        return $this->transports[$transport]->send($message);
    }
}
