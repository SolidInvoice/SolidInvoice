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
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Message\SendInvoiceReminderMessage;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;
use function assert;
use function Sentry\captureException;
use function Sentry\withMonitor;
use function sprintf;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Command\SendInvoiceRemindersCommandTest
 */
#[AsCommand(
    name: 'solidinvoice:invoices:send-reminders',
    description: 'Send payment reminders for pending and overdue invoices',
)]
// Every hour at a hashed minute to spread load. The hash is seeded from the full command line, so
// the two types land on different minutes rather than contending for the same one.
#[AsCronTask(expression: '#hourly', arguments: ['type' => 'pre-due'], schedule: 'invoice_reminders')]
#[AsCronTask(expression: '#hourly', arguments: ['type' => 'overdue'], schedule: 'invoice_reminders')]
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
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addArgument(name: 'type', mode: InputArgument::REQUIRED, suggestedValues: ['pre-due', 'overdue']);
    }

    /**
     * @throws Throwable
     */
    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(Invoice::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Disable company filter to query across all companies
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->suspend('company');
        }

        try {
            $count = match ($this->io->getArgument('type')) {
                'pre-due' => withMonitor('pre_due_invoice_reminders', $this->dispatchPreDueReminders(...)),
                'overdue' => withMonitor('overdue_invoice_reminders', $this->dispatchOverdueReminders(...)),
                default => throw new InvalidArgumentException(sprintf('Invalid type "%s"', $this->io->getArgument('type'))),
            };
        } catch (Throwable $e) {
            captureException($e);
            throw $e;
        } finally {
            // Re-enable company filter if it was enabled.
            //
            // The scan itself leaves the suspension intact, but SendInvoiceReminderHandler switches
            // tenants — and switchCompany() drops the suspension — so a synchronously routed
            // message would leave nothing for restore() to restore. It throws in that case, which
            // in a finally block would mask any exception already on its way out.
            if ($companyFilterEnabled) {
                if ($filters->isSuspended('company')) {
                    $filters->restore('company');
                } elseif (! $filters->isEnabled('company')) {
                    $filters->enable('company');
                }
            }
        }

        $this->io->success(sprintf(
            'Dispatched %d %s reminder messages',
            $count,
            $this->io->getArgument('type'),
        ));

        return self::SUCCESS;
    }

    private function dispatchPreDueReminders(): int
    {
        $this->io->comment('Processing pre-due reminders...');

        $count = 0;

        // Pre-due reminders fire a company-configured number of days before the due date, so one
        // scan per distinct window covers every company that shares it.
        foreach ($this->invoiceRepository->getConfiguredPreDueDays() as $daysBeforeDue => $settingValues) {
            try {
                foreach ($this->invoiceRepository->getInvoicesNeedingPreDueReminders($daysBeforeDue, $settingValues) as $candidate) {
                    $daysUntilDue = null;

                    if ($candidate['due'] instanceof DateTimeInterface) {
                        $daysUntilDue = CarbonImmutable::instance($this->clock->now())
                            ->startOfDay()
                            ->diff($candidate['due'])
                            ->days;
                    }

                    $this->messageBus->dispatch(
                        new SendInvoiceReminderMessage(
                            $candidate['invoiceId'],
                            $candidate['companyId'],
                            ReminderType::PreDue,
                            $daysUntilDue
                        )
                    );

                    ++$count;
                }
            } catch (DateMalformedStringException | ExceptionInterface $e) {
                captureException($e);
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }

        return $count;
    }

    private function dispatchOverdueReminders(): int
    {
        $this->io->comment('Processing overdue reminders...');

        $count = 0;

        // Overdue reminders use fixed intervals (1, 7, 14 days) but respect the global enable
        // setting, which the query filters on. One scan per interval covers every company.
        foreach ($this->reminderTypes as $days => $type) {
            $count += withMonitor(sprintf('overdue_invoice_reminders_%d_day', $days), function () use ($days, $type): int {
                $count = 0;

                if ($this->io->isVerbose()) {
                    $this->io->comment(sprintf('Processing %d-day overdue reminders', $days));
                }

                try {
                    foreach ($this->invoiceRepository->getInvoicesNeedingOverdueReminders($days, $type) as $candidate) {
                        $this->messageBus->dispatch(
                            new SendInvoiceReminderMessage(
                                $candidate['invoiceId'],
                                $candidate['companyId'],
                                $type
                            )
                        );

                        ++$count;
                    }
                } catch (Throwable $e) {
                    captureException($e);
                    $this->logger->error($e->getMessage(), ['exception' => $e]);
                }

                return $count;
            });
        }

        return $count;
    }
}
