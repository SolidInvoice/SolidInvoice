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

namespace SolidInvoice\EInvoiceBundle\Tests\Channel;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\EInvoiceBundle\Channel\TransmissionResult;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\TransmissionState;
use SolidInvoice\EInvoiceBundle\Test\Channel\InMemoryChannel;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Uid\Ulid;

final class InMemoryChannelTest extends TestCase
{
    public function testIdentifierAndLabel(): void
    {
        $channel = new InMemoryChannel();

        self::assertSame('in-memory', $channel->getIdentifier());

        $label = $channel->getLabel();
        self::assertInstanceOf(TranslatableMessage::class, $label);
        self::assertSame('In-memory test channel', $label->getMessage());
    }

    public function testSupportsMatchesOnlyItsOwnIdentifier(): void
    {
        $channel = new InMemoryChannel();

        self::assertTrue($channel->supports($this->document('in-memory')));
        self::assertFalse($channel->supports($this->document('storecove')));
    }

    public function testSendDefaultsToInProgressWhenNothingIsScripted(): void
    {
        $channel = new InMemoryChannel();
        $document = $this->document('in-memory');

        $result = $channel->send($document);

        self::assertSame(TransmissionState::InProgress, $result->state);
        self::assertSame('in-memory-' . $document->getId(), $result->externalId);
        self::assertSame([$document], $channel->sent);
    }

    public function testPollDefaultsToInProgressWhenNothingIsScripted(): void
    {
        $channel = new InMemoryChannel();
        $document = $this->document('in-memory');

        $result = $channel->poll($document);

        self::assertSame(TransmissionState::InProgress, $result->state);
        self::assertSame('in-memory-' . $document->getId(), $result->externalId);
        self::assertSame([$document], $channel->polled);
    }

    public function testWillSendScriptsFifoAndIndependentlyOfWillPoll(): void
    {
        $channel = new InMemoryChannel();
        $document = $this->document('in-memory');

        $accepted = new TransmissionResult(TransmissionState::Accepted, externalId: 'EXT-1');
        $rejected = new TransmissionResult(TransmissionState::Rejected, externalId: 'EXT-2');
        $channel->willSend($accepted);
        $channel->willSend($rejected);

        self::assertSame($accepted, $channel->send($document));
        self::assertSame($rejected, $channel->send($document));
        // The queue drained after two sends; a third falls back to the default.
        self::assertSame(TransmissionState::InProgress, $channel->send($document)->state);
    }

    public function testWillPollScriptsFifoIndependentlyOfWillSend(): void
    {
        $channel = new InMemoryChannel();
        $document = $this->document('in-memory');

        $polled = new TransmissionResult(TransmissionState::Accepted, receipt: '<UPO/>');
        $channel->willPoll($polled);
        $channel->willSend(new TransmissionResult(TransmissionState::Rejected));

        self::assertSame($polled, $channel->poll($document));
        self::assertSame(TransmissionState::Rejected, $channel->send($document)->state);
    }

    public function testWillSendCanScriptAnException(): void
    {
        $channel = new InMemoryChannel();
        $exception = new RuntimeException('platform timeout');
        $channel->willSend($exception);

        $this->expectExceptionObject($exception);

        $channel->send($this->document('in-memory'));
    }

    public function testAlwaysFailWithIsStickyAndOverridesTheQueue(): void
    {
        $channel = new InMemoryChannel();
        $document = $this->document('in-memory');

        $channel->willSend(new TransmissionResult(TransmissionState::Accepted));
        $exception = new RuntimeException('platform down');
        $channel->alwaysFailWith($exception);

        $this->expectExceptionObject($exception);

        try {
            $channel->send($document);
        } finally {
            // Sticky: a second call throws the same exception again, not the queued result.
            try {
                $channel->send($document);
                self::fail('Expected the second send() to throw too.');
            } catch (RuntimeException $second) {
                self::assertSame($exception, $second);
            }
        }
    }

    public function testResetClearsScriptingAndRecords(): void
    {
        $channel = new InMemoryChannel();
        $document = $this->document('in-memory');

        $channel->willSend(new TransmissionResult(TransmissionState::Accepted));
        $channel->alwaysFailWith(new RuntimeException('platform down'));

        try {
            $channel->send($document);
        } catch (RuntimeException) {
            // Expected: alwaysFailWith() is sticky. $document still landed in $sent
            // before the throw — that is what reset() below is proving it clears.
        }

        $channel->reset();

        self::assertSame([], $channel->sent);
        self::assertSame([], $channel->polled);
        self::assertSame(TransmissionState::InProgress, $channel->send($document)->state);
    }

    private function document(string $channel): EInvoiceDocument
    {
        $document = $this->createStub(EInvoiceDocument::class);
        $document->method('getId')->willReturn(new Ulid());
        $document->method('getChannel')->willReturn($channel);

        return $document;
    }
}
