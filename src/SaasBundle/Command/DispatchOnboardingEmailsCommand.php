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

namespace SolidInvoice\SaasBundle\Command;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SaasBundle\Onboarding\OnboardingDispatcher;
use SolidInvoice\SaasBundle\Onboarding\Step\FreeFeedbackStep;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;
use function assert;
use function function_exists;
use function Sentry\captureException;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:saas:dispatch-onboarding-emails',
    description: 'Dispatch the next due onboarding email for each user on a trial, or in their first two months on Free',
)]
#[AsCronTask(expression: '#hourly', schedule: 'onboarding_emails')]
final class DispatchOnboardingEmailsCommand extends Command
{
    // The last Free step is due a month in; the window bounds the scan.
    private const int FREE_WINDOW_DAYS = 2 * FreeFeedbackStep::AFTER_DAYS;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly OnboardingDispatcher $dispatcher,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(Trial::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Trials span companies; suspend the CompanyFilter for the duration of
        // the scan so queries can see every tenant's data. suspend()/restore()
        // preserves the filter instance and its parameters — disable()/enable()
        // would drop the companyId and risk re-enabling an unscoped filter.
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->suspend('company');
        }

        $processed = 0;

        try {
            $now = CarbonImmutable::instance($this->clock->now());

            // A user on several companies is ticked once per run, for the first
            // subscription found: their trial, if they have one. One email per
            // run, and each step judged against one account.
            $seen = [];

            foreach ($this->recipients($entityManager, $now) as [$user, $subscription]) {
                $userId = $user->getId()->toString();

                if (isset($seen[$userId])) {
                    continue;
                }

                $seen[$userId] = true;
                ++$processed;

                try {
                    $this->dispatcher->tick($user, $subscription);
                } catch (Throwable $e) {
                    if (function_exists('Sentry\\captureException')) {
                        captureException($e);
                    }

                    $this->logger->error('Onboarding dispatcher failed for user', [
                        'user_id' => $user->getId()->toString(),
                        'subscription_id' => $subscription->getId()->toBase58(),
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            if ($companyFilterEnabled) {
                $filters->restore('company');
            }
        }

        $this->io->success(sprintf('Processed %d active trials and Free accounts', $processed));

        return self::SUCCESS;
    }

    /**
     * Accounts still deciding: a running trial, or Free that started (signed up
     * or moved there) within the window. Trial-row subscriptions come first.
     *
     * A subscription with a Trial row emails the user who started it. One
     * without (a Free-default signup) emails the company's users. Which of them
     * actually get a step is OnboardingAudience's call: Free and Any steps only
     * reach new users, so long-standing members and customers are skipped.
     *
     * Subscription.status is not flipped when a trial lapses (the app only
     * compares endDate against now), so expired trials are filtered here.
     *
     * @return iterable<array{User, Subscription}>
     */
    private function recipients(EntityManagerInterface $entityManager, CarbonImmutable $now): iterable
    {
        $deciding = static fn (QueryBuilder $qb): QueryBuilder => $qb
            ->innerJoin('s.plan', 'p')
            ->addSelect('p')
            ->andWhere('(s.status = :trial AND s.endDate > :now) OR (s.status = :active AND p.price = 0 AND p.planId = :freePlanId AND s.startDate > :freeSince)')
            ->setParameter('trial', SubscriptionStatus::TRIAL)
            ->setParameter('active', SubscriptionStatus::ACTIVE)
            ->setParameter('now', $now)
            ->setParameter('freePlanId', '0')
            ->setParameter('freeSince', $now->subDays(self::FREE_WINDOW_DAYS));

        $withTrial = $deciding(
            $entityManager->createQueryBuilder()
                ->select('t', 's', 'u', 'c')
                ->from(Trial::class, 't')
                ->innerJoin('t.subscription', 's')
                ->innerJoin('t.user', 'u')
                ->innerJoin('s.subscriber', 'c'),
        )->getQuery();

        foreach ($withTrial->toIterable() as $trial) {
            assert($trial instanceof Trial);
            $user = $trial->getUser();

            if ($user instanceof User) {
                yield [$user, $trial->getSubscription()];
            }
        }

        $withoutTrial = $deciding(
            $entityManager->createQueryBuilder()
                ->select('s', 'c')
                ->from(Subscription::class, 's')
                ->innerJoin('s.subscriber', 'c')
                ->where('NOT EXISTS (SELECT t2.id FROM ' . Trial::class . ' t2 WHERE t2.subscription = s)'),
        )->getQuery();

        foreach ($withoutTrial->toIterable() as $subscription) {
            assert($subscription instanceof Subscription);
            $company = $subscription->getSubscriber();

            if (! $company instanceof Company) {
                continue;
            }

            foreach ($company->getUsers() as $user) {
                yield [$user, $subscription];
            }
        }
    }
}
