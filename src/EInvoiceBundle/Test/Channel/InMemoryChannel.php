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

namespace SolidInvoice\EInvoiceBundle\Test\Channel;

use SolidInvoice\EInvoiceBundle\Channel\ChannelInterface;
use SolidInvoice\EInvoiceBundle\Channel\TransmissionResult;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\TransmissionState;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Throwable;

/**
 * Registered only in the test environment (see config/services_test.php). Not readonly:
 * it is scriptable, and the handler under test must resolve the same shared instance that
 * the test scripted.
 */
final class InMemoryChannel implements ChannelInterface
{
    public const string IDENTIFIER = 'in-memory';

    /**
     * @var list<EInvoiceDocument>
     */
    public array $sent = [];

    /**
     * @var list<EInvoiceDocument>
     */
    public array $polled = [];

    /**
     * @var list<TransmissionResult|Throwable>
     */
    private array $sendQueue = [];

    /**
     * @var list<TransmissionResult|Throwable>
     */
    private array $pollQueue = [];

    private ?Throwable $alwaysFail = null;

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getLabel(): TranslatableInterface
    {
        return new TranslatableMessage('In-memory test channel');
    }

    public function supports(EInvoiceDocument $document): bool
    {
        return $document->getChannel() === self::IDENTIFIER;
    }

    public function send(EInvoiceDocument $document): TransmissionResult
    {
        $this->sent[] = $document;

        return $this->resolve($this->sendQueue, $document);
    }

    public function poll(EInvoiceDocument $document): TransmissionResult
    {
        $this->polled[] = $document;

        return $this->resolve($this->pollQueue, $document);
    }

    /**
     * Queues the next result or exception `send()` returns or throws. FIFO.
     */
    public function willSend(TransmissionResult | Throwable $outcome): void
    {
        $this->sendQueue[] = $outcome;
    }

    /**
     * Queues the next result or exception `poll()` returns or throws. FIFO.
     */
    public function willPoll(TransmissionResult | Throwable $outcome): void
    {
        $this->pollQueue[] = $outcome;
    }

    /**
     * Every call to `send()` or `poll()` throws this until `reset()`, regardless of what is
     * queued. For the dead-letter test, which needs several consecutive failures.
     */
    public function alwaysFailWith(Throwable $outcome): void
    {
        $this->alwaysFail = $outcome;
    }

    public function reset(): void
    {
        $this->sent = [];
        $this->polled = [];
        $this->sendQueue = [];
        $this->pollQueue = [];
        $this->alwaysFail = null;
    }

    /**
     * @param list<TransmissionResult|Throwable> $queue
     */
    private function resolve(array &$queue, EInvoiceDocument $document): TransmissionResult
    {
        if ($this->alwaysFail instanceof Throwable) {
            throw $this->alwaysFail;
        }

        if ([] !== $queue) {
            $outcome = array_shift($queue);

            if ($outcome instanceof Throwable) {
                throw $outcome;
            }

            return $outcome;
        }

        return new TransmissionResult(TransmissionState::InProgress, externalId: 'in-memory-' . $document->getId());
    }
}
