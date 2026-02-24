<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Command;

use Carbon\CarbonInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;
use function assert;
use function Sentry\withMonitor;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:recurring:send-invoices',
    description: 'Send recurring invoices',
)]
#[AsCronTask('#hourly', schedule: 'send_recurring_invoices')]
final class SendRecurringInvoicesCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly RecurringInvoiceRepository $recurringInvoiceRepository,
        private readonly RecurringSchedule $recurringSchedule,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(RecurringInvoice::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Disable company filter to query across all companies
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        try {
            withMonitor('send-recurring-invoices', function () use ($entityManager): void {
                $recurringInvoices = $this->recurringInvoiceRepository->getActiveRecurringInvoices();

                foreach ($recurringInvoices as $recurringInvoice) {
                    try {
                        $endDate = $this->recurringSchedule->getEndDate($recurringInvoice->getRecurringOptions());

                        if ($endDate instanceof CarbonInterface && ($endDate->isToday() || $endDate->isPast())) {
                            $recurringInvoice->setStatus(RecurringInvoiceStatus::Complete);
                            $entityManager->persist($recurringInvoice);
                        }

                        $nextRunDate = $this->recurringSchedule->getNextRunDate($recurringInvoice->getRecurringOptions());

                        if ($nextRunDate instanceof CarbonInterface && $nextRunDate->isToday() && ! $recurringInvoice->hasInvoiceForDay($nextRunDate)) {
                            $this->bus->dispatch(new CreateInvoiceFromRecurring($recurringInvoice->getId()));
                        }
                    } catch (Throwable $e) {
                        $this->logger->error(
                            sprintf('Error processing recurring invoice (%s): %s', $recurringInvoice->getId()?->toString(), $e->getMessage()),
                            ['exception' => $e]
                        );
                    }
                }

                $entityManager->flush();
            });
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        return 0;
    }
}
