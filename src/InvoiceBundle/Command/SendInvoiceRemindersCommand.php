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

namespace SolidInvoice\InvoiceBundle\Command;

use Carbon\CarbonImmutable;
use DateMalformedStringException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Message\SendInvoiceReminderMessage;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Uid\Ulid;
use Throwable;
use function assert;
use function Sentry\captureException;
use function Sentry\withMonitor;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:invoices:send-reminders',
    description: 'Send payment reminders for pending and overdue invoices',
)]
#[AsCronTask(expression: '#hourly', schedule: 'invoice_reminders')] // Every hour at a hashed minute to spread load
final class SendInvoiceRemindersCommand extends Command
{
    /**
     * @var array<int, ReminderType>
     */
    private array $reminderTypes = [
        1 => ReminderType::Overdue1,
        7 => ReminderType::Overdue7,
        14 => ReminderType::Overdue14,
    ];

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly ClockInterface $clock,
        private readonly CompanySelector $companySelector,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(Invoice::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Disable company filter to query across all companies
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        try {
            // Dispatch pre-due reminder messages
            $preDueCount = withMonitor('pre_due_invoice_reminders', $this->dispatchPreDueReminders(...));

            // Dispatch overdue reminder messages
            $overdueCount = $this->dispatchOverdueReminders();
        } catch (Throwable $e) {
            captureException($e);
            throw $e;
        } finally {
            // Re-enable company filter if it was enabled
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf(
            'Dispatched %d pre-due reminder messages and %d overdue reminder messages',
            $preDueCount,
            $overdueCount
        ));

        return self::SUCCESS;
    }

    private function dispatchPreDueReminders(): int
    {
        $this->io->comment('Processing pre-due reminders...');

        $companyRepository = $this->registry->getRepository(Company::class);

        $companies = $companyRepository->createQueryBuilder('c')
            ->select('DISTINCT(c.id) as companyId', 's_days.value as preDueDays')
            ->innerJoin('c.settings', 's_days', 'WITH', 's_days.key = :key_days')
            ->innerJoin('c.settings', 's_rem', 'WITH', 's_rem.key = :key_rem AND s_rem.value = :val_true')
            ->innerJoin('c.settings', 's_pre', 'WITH', 's_pre.key = :key_pre AND s_pre.value = :val_true')
            ->getQuery()
            ->toIterable([
                'key_days' => 'invoice/reminder/pre_due_days',
                'key_rem' => 'invoice/reminder/enabled',
                'key_pre' => 'invoice/reminder/pre_due_enabled',
                'val_true' => '1',
            ], AbstractQuery::HYDRATE_SCALAR);

        $count = 0;

        foreach ($companies as $company) {
            $companyId = Ulid::fromString($company['companyId']);

            $this->companySelector->switchCompany($companyId);

            try {
                foreach ($this->invoiceRepository->getInvoicesNeedingPreDueReminders((int) $company['preDueDays']) as $invoice) {
                    $daysUntilDue = null;
                    if ($invoice->getDue()) {
                        $daysUntilDue = CarbonImmutable::instance($this->clock->now())->startOfDay()->diff($invoice->getDue())->days;
                    }

                    $this->messageBus->dispatch(
                        new SendInvoiceReminderMessage(
                            $invoice->getId(),
                            $companyId,
                            ReminderType::PreDue,
                            $daysUntilDue
                        )
                    );

                    ++$count;
                }
            } catch (DateMalformedStringException | ExceptionInterface $e) {
                captureException($e);
            } finally {
                $this->companySelector->reset();
            }
        }

        return $count;
    }

    private function dispatchOverdueReminders(): int
    {
        $this->io->comment('Processing overdue reminders...');

        $count = 0;

        // Get companies with reminders enabled
        // Overdue reminders use fixed intervals (1, 7, 14 days) but respect the global enable setting

        $companyRepository = $this->registry->getRepository(Company::class);

        $companies = $companyRepository->createQueryBuilder('c')
            ->select('DISTINCT(c.id) as companyId')
            ->innerJoin('c.settings', 's_rem', 'WITH', 's_rem.key = :key_rem AND s_rem.value = :val_true')
            ->getQuery()
            ->toIterable([
                'key_rem' => 'invoice/reminder/enabled',
                'val_true' => '1',
            ], AbstractQuery::HYDRATE_SCALAR);

        foreach ($companies as $company) {
            $companyId = Ulid::fromString($company['companyId']);

            $this->companySelector->switchCompany($companyId);

            try {
                foreach ($this->reminderTypes as $days => $type) {
                    $count += withMonitor(sprintf('overdue_invoice_reminders_%d_day', $days), function () use ($days, $type, $companyId) {
                        $count = 0;
                        if ($this->io->isVerbose()) {
                            $this->io->comment(sprintf('Processing %d-day overdue reminders', $days));
                        }

                        foreach ($this->invoiceRepository->getInvoicesNeedingOverdueReminders($days, $type) as $invoice) {
                            $this->messageBus->dispatch(
                                new SendInvoiceReminderMessage(
                                    $invoice->getId(),
                                    $companyId,
                                    $type
                                )
                            );

                            ++$count;
                        }

                        return $count;
                    });
                }
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            } finally {
                $this->companySelector->reset();
            }
        }

        return $count;
    }
}
