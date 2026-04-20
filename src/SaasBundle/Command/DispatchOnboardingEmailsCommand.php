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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\SaasBundle\Onboarding\OnboardingDispatcher;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;
use function assert;
use function function_exists;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:saas:dispatch-onboarding-emails',
    description: 'Dispatch the next due onboarding email for each user currently on a trial',
)]
#[AsCronTask(expression: '#hourly', schedule: 'onboarding_emails')]
final class DispatchOnboardingEmailsCommand extends Command
{
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
            // Subscription.status is not flipped when a trial lapses — the app
            // only compares endDate against now — so filter expired trials out
            // here to keep this scan bounded as historical trial volume grows.
            $qb = $entityManager->createQueryBuilder()
                ->select('t')
                ->from(Trial::class, 't')
                ->innerJoin('t.subscription', 's')
                ->where('s.status = :status')
                ->andWhere('s.endDate > :now')
                ->setParameter('status', SubscriptionStatus::TRIAL)
                ->setParameter('now', $this->clock->now());

            foreach ($qb->getQuery()->toIterable() as $trial) {
                assert($trial instanceof Trial);

                $user = $trial->getUser();
                $subscription = $trial->getSubscription();

                if (! $user instanceof User) {
                    continue;
                }

                ++$processed;

                try {
                    $this->dispatcher->tick($user, $subscription);
                } catch (Throwable $e) {
                    if (function_exists('Sentry\\captureException')) {
                        \Sentry\captureException($e);
                    }
                    $this->logger->error('Onboarding dispatcher failed for user', [
                        'user_id' => $user->getId()?->toString(),
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

        $this->io->success(sprintf('Processed %d active trials', $processed));

        return self::SUCCESS;
    }
}
