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

namespace SolidInvoice\SaasBundle\EventSubscriber;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidWorx\Platform\SaasBundle\Dto\LemonSqueezy\Subscription as LemonSqueezySubscription;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\LemonSqueezy\SubscriptionStatus as RemoteStatus;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionCreatedEvent;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function sprintf;

/**
 * Checking out during a running no-card trial must not add a second trial: the
 * user keeps the plan from the date the banner showed, not N days later.
 *
 * Lemon Squeezy still applies the variant's own trial at checkout (it is how a
 * card is taken without charging), so when the subscription is created this
 * pulls its `trial_ends_at` back to the local trial's end. It runs before the
 * platform's handler, and hands it the corrected date so the local end date
 * never moves.
 *
 * A failed call is logged, not thrown: the webhook must still be recorded, and
 * the worst case is the extra provider trial this exists to prevent.
 *
 * @see \SolidInvoice\SaasBundle\Tests\EventSubscriber\ProviderTrialAlignmentListenerTest
 */
final readonly class ProviderTrialAlignmentListener
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private BillingMode $billingMode,
        private ClockInterface $clock,
        #[Target('lemon_squeezy')]
        private HttpClientInterface $lemonSqueezy,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: SubscriptionCreatedEvent::class, priority: 10)]
    public function onSubscriptionCreated(SubscriptionCreatedEvent $event): void
    {
        $remote = $event->subscription;

        // In paid-trial mode checkout is how the trial starts, so the provider's
        // trial is the only one.
        if (! $remote instanceof LemonSqueezySubscription || $this->billingMode->requiresCardForTrial()) {
            return;
        }

        $remoteEnd = $remote->attributes->trialEndsAt;
        $localEnd = $this->runningTrialEnd($this->subscriptionRepository->findOneBy(['id' => $event->subscriptionId]));

        if ($remote->attributes->status !== RemoteStatus::ON_TRIAL || ! $remoteEnd instanceof DateTimeInterface || ! $localEnd instanceof DateTimeImmutable || $remoteEnd <= $localEnd) {
            return;
        }

        try {
            $response = $this->lemonSqueezy->request('PATCH', 'subscriptions/' . $event->externalId, [
                'json' => [
                    'data' => [
                        'type' => 'subscriptions',
                        'id' => $event->externalId,
                        'attributes' => ['trial_ends_at' => $localEnd->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM)],
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 300) {
                throw new RuntimeException(sprintf('HTTP %d', $response->getStatusCode()));
            }
        } catch (ExceptionInterface | RuntimeException $e) {
            $this->logger->error('Could not align the provider trial with the local trial end', [
                'subscription_id' => $event->subscriptionId->toBase58(),
                'external_id' => $event->externalId,
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        $remote->attributes->trialEndsAt = $localEnd;
    }

    private function runningTrialEnd(?Subscription $subscription): ?DateTimeImmutable
    {
        if (! $subscription instanceof Subscription || $subscription->getStatus() !== SubscriptionStatus::TRIAL) {
            return null;
        }

        $end = DateTimeImmutable::createFromInterface($subscription->getEndDate());

        return $end > $this->clock->now() ? $end : null;
    }
}
