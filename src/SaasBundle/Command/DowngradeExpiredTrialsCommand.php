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
use SolidInvoice\SaasBundle\Message\SendTrialDowngradedEmailMessage;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;
use function assert;
use function count;
use function function_exists;
use function Sentry\captureException;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:saas:downgrade-expired-trials',
    description: 'Downgrade subscriptions whose trial has expired to the free plan',
)]
#[AsCronTask(expression: '#hourly', schedule: 'downgrade_expired_trials')]
final class DowngradeExpiredTrialsCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly MessageBusInterface $bus,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'List the trials that would be downgraded without changing anything.');
    }

    protected function handle(): int
    {
        $dryRun = (bool) $this->io->getOption('dry-run');

        $entityManager = $this->registry->getManagerForClass(Subscription::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Trials span companies; suspend the CompanyFilter for the scan so the
        // query sees every tenant's subscriptions. suspend()/restore() preserves
        // the filter's companyId — disable()/enable() would drop it.
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->suspend('company');
        }

        $downgraded = 0;
        $failed = 0;

        try {
            $expiredTrials = $this->subscriptionRepository->findExpiredTrials($this->clock->now());

            if ($dryRun) {
                $rows = [];

                foreach ($expiredTrials as $subscription) {
                    $rows[] = [
                        $subscription->getId()->toBase58(),
                        $subscription->getPlan()->getName(),
                        $subscription->getEndDate()->format('Y-m-d H:i:s'),
                    ];
                }

                $this->io->table(['Subscription', 'Plan', 'Trial ended'], $rows);
                $this->io->note(sprintf('[dry-run] %d expired trial(s) would be downgraded to the free plan.', count($rows)));

                return self::SUCCESS;
            }

            foreach ($expiredTrials as $subscription) {
                try {
                    $this->subscriptionManager->downgradeToFree($subscription);
                    $this->bus->dispatch(new SendTrialDowngradedEmailMessage($subscription->getId()));
                    ++$downgraded;
                } catch (Throwable $e) {
                    ++$failed;

                    if (function_exists('Sentry\\captureException')) {
                        captureException($e);
                    }

                    $this->logger->error('Failed to auto-downgrade expired trial', [
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

        $this->io->success(sprintf('Downgraded %d expired trial(s) to the free plan (%d failed).', $downgraded, $failed));

        return self::SUCCESS;
    }
}
