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
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:recurring:send-invoices',
    description: 'Send recurring invoices',
)]
#[AsScheduledTask('#daily')]
final class SendRecurringInvoicesCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly RecurringInvoiceRepository $recurringInvoiceRepository,
        private readonly RecurringSchedule $recurringSchedule,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entityManager = $this->registry->getManagerForClass(RecurringInvoice::class);
        assert($entityManager instanceof EntityManagerInterface);

        $recurringInvoices = $this->recurringInvoiceRepository->getActiveRecurringInvoices();

        foreach ($recurringInvoices as $recurringInvoice) {
            $endDate = $this->recurringSchedule->getEndDate($recurringInvoice->getRecurringOptions());

            if ($endDate instanceof CarbonInterface && ($endDate->isToday() || $endDate->isPast())) {
                $recurringInvoice->setStatus('complete');
                $entityManager->persist($recurringInvoice);
            }

            $nextRunDate = $this->recurringSchedule->getNextRunDate($recurringInvoice->getRecurringOptions());

            if ($nextRunDate instanceof CarbonInterface && $nextRunDate->isToday()) {
                try {
                    $this->bus->dispatch(new CreateInvoiceFromRecurring($recurringInvoice));
                } catch (ExceptionInterface $e) {
                    $io->error(sprintf('Error sending recurring invoice (%s): %s', $recurringInvoice->getId(), $e->getMessage()));
                }
            }
        }

        $entityManager->flush();

        return 0;
    }
}
