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
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;
use Symfony\Component\Scheduler\Trigger\PeriodicalTrigger;
use Symfony\Component\Scheduler\Trigger\TriggerInterface;

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
            function (Event $event, ?EventHint $hint, ?Scope $scope): EventId {
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

    public function testCapturesExceptionInSentryIssuesOnFailure(): void
    {
        // The global error handler is not guaranteed to run in scheduler/queue contexts,
        // so the middleware must explicitly call captureException() to ensure the error
        // appears in Sentry Issues and is not only visible via the cron monitor status.
        $exception = new \RuntimeException('job failed');

        // Use a fresh hub so we can assert captureException() is called exactly once.
        $client = $this->createMock(ClientInterface::class);
        $client->method('getOptions')->willReturn(new Options());
        $client->method('captureEvent')->willReturnCallback(
            function (Event $event, ?EventHint $hint, ?Scope $scope): EventId {
                $this->capturedEvents[] = $event;

                return $event->getId();
            }
        );
        $client->expects(self::once())
            ->method('captureException')
            ->with($exception, self::anything(), self::anything())
            ->willReturn(EventId::generate());
        SentrySdk::setCurrentHub(new Hub($client));

        $envelope = $this->makeScheduledEnvelope('app:test');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('handle')->willThrowException($exception);

        $next = $this->createMock(StackInterface::class);
        $next->method('next')->willReturn($middleware);

        try {
            $this->middleware->handle($envelope, $next);
        } catch (\RuntimeException) {
        }
    }

    public function testCapturesErrorCheckInAndRethrowsOnException(): void
    {
        $envelope = $this->makeScheduledEnvelope('solidinvoice:invoices:mark-overdue');
        $exception = new \RuntimeException('job failed');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('handle')->willThrowException($exception);

        $next = $this->createMock(StackInterface::class);
        $next->method('next')->willReturn($middleware);

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
        self::assertSame('test_schedule-solidinvoice-invoices-mark-overdue', $checkInEvents[0]->getCheckIn()?->getMonitorSlug());
    }

    public function testSlugFromRunCommandMessageStripsArguments(): void
    {
        // Only the command name (first token) should form the slug, not any arguments.
        $envelope = $this->makeScheduledEnvelope('app:test --force');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertSame('test_schedule-app-test', $checkInEvents[0]->getCheckIn()?->getMonitorSlug());
    }

    public function testSlugFromArbitraryMessageClass(): void
    {
        // Named class: short name converted to kebab-case.
        $message = new SchedulerMessageFixture();
        $envelope = $this->makeScheduledEnvelopeForMessage($message);
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertSame('test_schedule-schedulermessagefixture', $checkInEvents[0]->getCheckIn()?->getMonitorSlug());
    }

    public function testSlugFromAnonymousClassIsValidFormat(): void
    {
        // Anonymous class names contain @, /, . and other characters that are
        // invalid in Sentry monitor slugs. The result must still match ^[a-z0-9_-]+$.
        $message = new class() {};
        $envelope = $this->makeScheduledEnvelopeForMessage($message);
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $slug = $this->getCheckInEvents()[0]->getCheckIn()?->getMonitorSlug() ?? '';
        self::assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $slug);
    }

    public function testCheckInIdIsReusedBetweenInProgressAndCompletion(): void
    {
        $envelope = $this->makeScheduledEnvelope('app:test');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $checkInEvents = $this->getCheckInEvents();
        self::assertCount(2, $checkInEvents);

        // captureCheckIn() returns the CheckIn's own correlation ID (not the Event ID).
        // The middleware passes that ID as checkInId to the completion call so Sentry
        // can correlate the two check-ins. Both events must therefore share the same CheckIn ID.
        $inProgressCheckInId = $checkInEvents[0]->getCheckIn()?->getId();
        $completionCheckInId = $checkInEvents[1]->getCheckIn()?->getId();

        self::assertNotEmpty($inProgressCheckInId);
        self::assertSame($inProgressCheckInId, $completionCheckInId);
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

    public function testSlugWithinLimitIsNotTruncated(): void
    {
        // schedule(13) + '-' + command(8) = 22 chars — well within the 50-char limit.
        $envelope = $this->makeScheduledEnvelope('app:test', scheduleName: 'test_schedule');
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $slug = $this->getCheckInEvents()[0]->getCheckIn()?->getMonitorSlug() ?? '';
        self::assertSame('test_schedule-app-test', $slug);
    }

    public function testSlugIsTruncatedToFiftyCharacters(): void
    {
        // schedule(40) + '-' + message(16) = 57 chars — exceeds the 50-char limit.
        $envelope = $this->makeScheduledEnvelope(
            'app:test:command',
            scheduleName: str_repeat('a', 40),
        );
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $slug = $this->getCheckInEvents()[0]->getCheckIn()?->getMonitorSlug() ?? '';
        self::assertLessThanOrEqual(50, strlen($slug), 'Slug must not exceed 50 characters');
        self::assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $slug, 'Slug must only contain valid characters');
    }

    public function testSlugIsCappedAtFiftyCharactersExactly(): void
    {
        // Combined raw slug is longer than 50 chars; must be hard-capped at exactly 50.
        $envelope = $this->makeScheduledEnvelope(
            str_repeat('b', 15) . ':' . str_repeat('c', 15),
            scheduleName: str_repeat('a', 30),
        );
        $stack = $this->makeStack($envelope);

        $this->middleware->handle($envelope, $stack);

        $slug = $this->getCheckInEvents()[0]->getCheckIn()?->getMonitorSlug() ?? '';
        self::assertSame(50, strlen($slug));
        self::assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $slug);
    }

    public function testNoCheckInForNonCronTrigger(): void
    {
        // PeriodicalTrigger has no cron expression, so no MonitorConfig can be built.
        // The middleware must skip the check-in entirely to avoid orphaned unmanaged
        // check-ins in Sentry — but it should still complete without errors.
        $envelope = $this->makeScheduledEnvelope('app:test', trigger: new PeriodicalTrigger(60));
        $stack = $this->makeStack($envelope);

        $result = $this->middleware->handle($envelope, $stack);

        self::assertSame($envelope, $result);
        self::assertEmpty($this->getCheckInEvents(), 'No check-in events should be emitted for non-cron triggers');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeScheduledEnvelope(string $commandInput, bool $withReceived = true, ?TriggerInterface $trigger = null, string $scheduleName = 'test_schedule'): Envelope
    {
        return $this->makeScheduledEnvelopeForMessage(new RunCommandMessage($commandInput), $withReceived, $trigger, $scheduleName);
    }

    private function makeScheduledEnvelopeForMessage(object $message, bool $withReceived = true, ?TriggerInterface $trigger = null, string $scheduleName = 'test_schedule'): Envelope
    {
        $context = new MessageContext(
            name: $scheduleName,
            id: 'test-id-' . uniqid(),
            trigger: $trigger ?? CronExpressionTrigger::fromSpec('0 * * * *'),
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
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('handle')->willReturn($envelope);

        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($middleware);

        return $stack;
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

/**
 * Named fixture used by testSlugFromArbitraryMessageClass to produce a
 * predictable slug ("scheduler-message-fixture") without ReflectionClass.
 */
final class SchedulerMessageFixture
{
}
