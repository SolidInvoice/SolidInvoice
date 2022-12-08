<?php
declare(strict_types=1);

namespace SolidInvoice\InvoiceBundle\Schedule;

use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder as ScheduleBuilderInterface;

final class ScheduleBuilder implements ScheduleBuilderInterface
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function buildSchedule(Schedule $schedule): void
    {
        $invoiceRepository = $this->registry->getRepository(RecurringInvoice::class);

        /** @var RecurringInvoice[] $recurringInvoices */
        $recurringInvoices = $invoiceRepository->findBy(['status' => 'active']);

        foreach ($recurringInvoices as $recurringInvoice) {
            if (null !== ($recurringInvoice->getDateEnd())  && ! Carbon::instance($recurringInvoice->getDateEnd())->isFuture()) {
                continue;
            }

            $schedule
                ->addMessage(new CreateInvoiceFromRecurring($recurringInvoice))
                ->description(sprintf('Create recurring invoice (%d)', $recurringInvoice->getId()))
                ->cron($recurringInvoice->getFrequency())
            ;
        }
    }
}
