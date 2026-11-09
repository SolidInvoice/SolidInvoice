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

namespace SolidInvoice\SaasBundle\Tests\EventSubscriber;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SolidInvoice\SaasBundle\EventSubscriber\ProviderTrialAlignmentListener;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidWorx\Platform\SaasBundle\Dto\LemonSqueezy\Subscription as LemonSqueezySubscription;
use SolidWorx\Platform\SaasBundle\Dto\LemonSqueezy\SubscriptionAttributes;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\LemonSqueezy\SubscriptionStatus as RemoteStatus;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionCreatedEvent;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Uid\Ulid;
use function json_decode;

#[CoversClass(ProviderTrialAlignmentListener::class)]
final class ProviderTrialAlignmentListenerTest extends TestCase
{
    /**
     * @var list<array{method: string, url: string, body: mixed}>
     */
    private array $requests = [];

    /**
     * The trial ends on the 10th locally; Lemon Squeezy added its own 14 days.
     * Billing must start on the 10th, as the banner said.
     */
    public function testTheProviderTrialIsPulledBackToTheLocalTrialEnd(): void
    {
        $event = $this->event(RemoteStatus::ON_TRIAL, '2026-01-19');

        $this->listener($this->trial('2026-01-10'))->onSubscriptionCreated($event);

        self::assertCount(1, $this->requests);
        self::assertSame('PATCH', $this->requests[0]['method']);
        self::assertStringEndsWith('/subscriptions/ls-42', $this->requests[0]['url']);
        self::assertSame('2026-01-10T00:00:00+00:00', $this->requests[0]['body']['data']['attributes']['trial_ends_at']);
        // The platform handler runs next and saves this date, so the local end never moves.
        self::assertEquals(new DateTimeImmutable('2026-01-10', new DateTimeZone('UTC')), $event->subscription?->attributes->trialEndsAt);
    }

    public function testAnEndedOrMissingLocalTrialIsLeftAlone(): void
    {
        $this->listener($this->trial('2026-01-04'))->onSubscriptionCreated($this->event(RemoteStatus::ON_TRIAL, '2026-01-19'));
        $this->listener($this->trial('2026-01-10', SubscriptionStatus::ACTIVE))->onSubscriptionCreated($this->event(RemoteStatus::ON_TRIAL, '2026-01-19'));
        $this->listener(null)->onSubscriptionCreated($this->event(RemoteStatus::ON_TRIAL, '2026-01-19'));

        self::assertSame([], $this->requests);
    }

    public function testNothingIsChangedWhenTheProviderChargesStraightAway(): void
    {
        $this->listener($this->trial('2026-01-10'))->onSubscriptionCreated($this->event(RemoteStatus::ACTIVE, null));

        self::assertSame([], $this->requests);
    }

    /**
     * In paid-trial mode checkout starts the trial, so the provider's is the one.
     */
    public function testPaidTrialModeKeepsTheProviderTrial(): void
    {
        $this->listener($this->trial('2026-01-10'), BillingModeFactory::paidTrial())->onSubscriptionCreated($this->event(RemoteStatus::ON_TRIAL, '2026-01-19'));

        self::assertSame([], $this->requests);
    }

    /**
     * A failed call must not fail the webhook; the provider's date is kept.
     */
    public function testAFailedCallKeepsTheProviderDate(): void
    {
        $event = $this->event(RemoteStatus::ON_TRIAL, '2026-01-19');

        $this->listener($this->trial('2026-01-10'), status: 422)->onSubscriptionCreated($event);

        self::assertCount(1, $this->requests);
        self::assertEquals(CarbonImmutable::parse('2026-01-19'), $event->subscription?->attributes->trialEndsAt);
    }

    private function listener(?Subscription $local, ?BillingMode $billingMode = null, int $status = 200): ProviderTrialAlignmentListener
    {
        $repository = $this->createStub(SubscriptionRepositoryInterface::class);
        $repository->method('findOneBy')->willReturn($local);

        $client = new MockHttpClient(function (string $method, string $url, array $options) use ($status): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => $url, 'body' => json_decode((string) $options['body'], true)];

            return new MockResponse('{}', ['http_code' => $status]);
        }, 'https://api.lemonsqueezy.com/v1/');

        return new ProviderTrialAlignmentListener($repository, $billingMode ?? BillingModeFactory::freeTrial(), new MockClock('2026-01-05'), $client, new NullLogger());
    }

    private function trial(string $endDate, SubscriptionStatus $status = SubscriptionStatus::TRIAL): Subscription
    {
        return new Subscription()
            ->setPlan(new Plan()->setName('Solo')->setPlanId('solo')->setPrice(900))
            ->setStatus($status)
            ->setStartDate(CarbonImmutable::parse('2025-12-27'))
            ->setEndDate(new DateTimeImmutable($endDate, new DateTimeZone('UTC')));
    }

    private function event(RemoteStatus $status, ?string $trialEndsAt): SubscriptionCreatedEvent
    {
        $attributes = new SubscriptionAttributes();
        $attributes->status = $status;
        $attributes->trialEndsAt = $trialEndsAt !== null ? new DateTimeImmutable($trialEndsAt) : null;

        $dto = new LemonSqueezySubscription();
        $dto->attributes = $attributes;

        return new SubscriptionCreatedEvent(new Ulid(), 'ls-42', $dto);
    }
}
