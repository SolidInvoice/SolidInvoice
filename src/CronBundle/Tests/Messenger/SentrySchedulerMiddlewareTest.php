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

namespace SolidInvoice\CronBundle\Tests\Messenger;

use PHPUnit\Framework\TestCase;
use Sentry\CheckInStatus;
use Sentry\ClientInterface;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\EventId;
use Sentry\EventType;
use Sentry\Options;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\Scope;
use SolidInvoice\CronBundle\Messenger\SentrySchedulerMiddleware;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

/** @covers \SolidInvoice\CronBundle\Messenger\SentrySchedulerMiddleware */
final class SentrySchedulerMiddlewareTest extends TestCase
{
    private SentrySchedulerMiddleware $middleware;

    /**
     * @var list<Event>
     */
    private array $capturedEvents = [];

    protected function setUp(): void
    {
        $this->capturedEvents = [];
        $this->middleware = new SentrySchedulerMiddleware();

        $client = $this->createMock(ClientInterface::class);
        $client->method('getOptions')->willReturn(new Options());
        $client->method('captureEvent')->willReturnCallback(
            function (Event $event, ?EventHint $hint, ?Scope $scope): ?EventId {
                $this->capturedEvents[] = $event;

                return $event->getId();
            }
        );

        SentrySdk::setCurrentHub(new Hub($client));
    }

    protected function tearDown(): void
    {
        SentrySdk::setCurrentHub(new Hub());
    }

    public function testPassesThroughWhenNoScheduledStamp(): void
    {
        $envelope = new Envelope(new RunCommandMessage('app:test'));
        $stack = $this->makeStack($envelope);

        $result = $this->middleware->handle($envelope, $stack);

        self::assertSame($envelope, $result);
        self::assertEmpty($this->capturedEvents);
    }

    public function testPassesThroughWhenNoReceivedStamp(): void
    {
        $envelope = $this->makeScheduledEnvelope('app:test', withReceived: false);
        $stack = $this->makeStack($envelope);

        $result = $this->middleware->handle($envelope, $stack);

        self::assertSame($envelope, $result);
        self::assertEmpty($this->capturedEvents);
    }

    public function testPassesThroughWhenSentryClientNotConfigured(): void
    {
        SentrySdk::setCurrentHub(new Hub());

        $envelope = $this->makeScheduledEnvelope('app:test');
        $stack = $this->makeStack($envelope);

        $result = $this->middleware->handle($envelope, $stack);

        self::assertSame($envelope, $result);
        self::assertEmpty($this->capturedEvents);
    }

    public function testCapturesOkCheckInOnSuccess(): void
    {
        $envelope = $this->makeScheduledEnvelope('solidinvoice:invoices:mark-overdue');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertCount(2, $checkInEvents);
        self::assertSame(CheckInStatus::inProgress(), $checkInEvents[0]->getCheckIn()?->getStatus());
        self::assertSame(CheckInStatus::ok(), $checkInEvents[1]->getCheckIn()?->getStatus());
    }

    public function testCapturesErrorCheckInAndRethrowsOnException(): void
    {
        $envelope = $this->makeScheduledEnvelope('solidinvoice:invoices:mark-overdue');
        $exception = new \RuntimeException('job failed');

        $next = $this->createMock(StackInterface::class);
        $next->method('next')->willReturnSelf();
        $next->method('handle')->willThrowException($exception);

        $thrownException = null;

        try {
            $this->middleware->handle($envelope, $next);
        } catch (\RuntimeException $e) {
            $thrownException = $e;
        }

        self::assertSame($exception, $thrownException, 'Original exception must be rethrown unchanged');

        $checkInEvents = $this->getCheckInEvents();
        self::assertCount(2, $checkInEvents);
        self::assertSame(CheckInStatus::inProgress(), $checkInEvents[0]->getCheckIn()?->getStatus());
        self::assertSame(CheckInStatus::error(), $checkInEvents[1]->getCheckIn()?->getStatus());
    }

    public function testSlugFromRunCommandMessage(): void
    {
        $envelope = $this->makeScheduledEnvelope('solidinvoice:invoices:mark-overdue');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertSame('solidinvoice-invoices-mark-overdue', $checkInEvents[0]->getCheckIn()?->getMonitorSlug());
    }

    public function testSlugFromRunCommandMessageStripsArguments(): void
    {
        // Only the command name (first token) should form the slug, not any arguments.
        $envelope = $this->makeScheduledEnvelope('app:test --force');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertSame('app-test', $checkInEvents[0]->getCheckIn()?->getMonitorSlug());
    }

    public function testSlugFromArbitraryMessageClass(): void
    {
        // Non-RunCommandMessage uses the short class name in kebab-case.
        $message = new class() {};
        $envelope = $this->makeScheduledEnvelopeForMessage($message);
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertNotEmpty($checkInEvents[0]->getCheckIn()?->getMonitorSlug());
    }

    public function testCheckInIdIsReusedBetweenInProgressAndCompletion(): void
    {
        $envelope = $this->makeScheduledEnvelope('app:test');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertCount(2, $checkInEvents);

        // The middleware passes the EventId from the inProgress call as the checkInId
        // of the completion call so Sentry can correlate the two events.
        $inProgressEventId = (string) $checkInEvents[0]->getId();
        $completionCheckInId = $checkInEvents[1]->getCheckIn()?->getId();

        self::assertNotEmpty($inProgressEventId);
        self::assertSame($inProgressEventId, $completionCheckInId);
    }

    public function testDurationIsReportedOnCompletion(): void
    {
        $envelope = $this->makeScheduledEnvelope('app:test');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertNull($checkInEvents[0]->getCheckIn()?->getDuration(), 'inProgress check-in must not carry a duration');
        self::assertGreaterThanOrEqual(0.0, $checkInEvents[1]->getCheckIn()?->getDuration(), 'completion check-in must report elapsed seconds');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeScheduledEnvelope(string $commandInput, bool $withReceived = true): Envelope
    {
        return $this->makeScheduledEnvelopeForMessage(new RunCommandMessage($commandInput), $withReceived);
    }

    private function makeScheduledEnvelopeForMessage(object $message, bool $withReceived = true): Envelope
    {
        $context = new MessageContext(
            name: 'test_schedule',
            id: 'test-id-' . uniqid(),
            trigger: CronExpressionTrigger::fromSpec('0 * * * *'),
            triggeredAt: new \DateTimeImmutable(),
        );

        $stamps = [new ScheduledStamp($context)];

        if ($withReceived) {
            $stamps[] = new ReceivedStamp('scheduler_test');
        }

        return new Envelope($message, $stamps);
    }

    private function makeStack(Envelope $envelope): StackInterface
    {
        $next = $this->createMock(StackInterface::class);
        $next->method('next')->willReturnSelf();
        $next->method('handle')->willReturn($envelope);

        return $next;
    }

    /**
     * @return list<Event>
     */
    private function getCheckInEvents(): array
    {
        return array_values(
            array_filter(
                $this->capturedEvents,
                static fn (Event $e): bool => $e->getType() === EventType::checkIn(),
            )
        );
    }
}
